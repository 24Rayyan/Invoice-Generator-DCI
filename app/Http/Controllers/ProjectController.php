<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachProjectExpensesRequest;
use App\Http\Requests\AttachProjectInvoicesRequest;
use App\Http\Requests\ProjectRequest;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::with('client')
            ->withCount('invoices')
            ->withSum('invoices', 'total_amount')
            ->withSum('directExpenses', 'amount')
            ->orderByDesc('created_at')
            ->paginate(15);
        $customers = Customer::orderBy('name')->get();

        return view('projects.index', compact('projects', 'customers'));
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        Project::create($request->validated());

        return redirect()->route('projects.index')->with('success', 'Project berhasil dibuat.');
    }

    public function show(Project $project): View
    {
        $project->load([
            'client',
            'invoices.customer',
            'directExpenses.category',
        ]);
        $setting = Setting::getSetting();
        $customers = Customer::orderBy('name')->get();
        $revenue = (float) $project->invoices->sum('total_amount');
        $directExpense = (float) $project->directExpenses->sum('amount');
        $netProfit = $revenue - $directExpense;
        $netMargin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0.0;
        $unallocatedExpenses = Transaction::with('category')
            ->expense()
            ->unallocatedToProject()
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
        $unallocatedInvoices = Document::with('customer')
            ->where('type', 'INVOICE')
            ->whereNull('project_id')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return view('projects.show', compact(
            'project',
            'customers',
            'setting',
            'revenue',
            'directExpense',
            'netProfit',
            'netMargin',
            'unallocatedExpenses',
            'unallocatedInvoices'
        ));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()->route('projects.show', $project)->with('success', 'Project berhasil diperbarui.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $projectName = $project->project_name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project {$projectName} berhasil dihapus. Invoice dan transaksi terkait tetap tersimpan tanpa relasi project.");
    }

    public function attachExpenses(AttachProjectExpensesRequest $request, Project $project): RedirectResponse
    {
        $updated = DB::transaction(function () use ($request, $project) {
            $transactions = Transaction::query()
                ->whereIn('id', $request->validated('transaction_ids'))
                ->where('type', 'expense')
                ->whereNull('project_id')
                ->lockForUpdate()
                ->get();

            if ($transactions->isEmpty()) {
                return 0;
            }

            foreach ($transactions as $transaction) {
                $transaction->update(['project_id' => $project->id]);
            }

            ActivityLogger::log(
                ActivityLog::ACTION_UPDATE,
                'Project Management',
                'User '.(auth()->user()->username ?? 'Sistem')." menghubungkan {$transactions->count()} transaksi kas ke project \"{$project->project_name}\".",
                [
                    'project_id' => $project->id,
                    'project_name' => $project->project_name,
                    'transactions' => $transactions->map(fn (Transaction $transaction) => [
                        'id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number,
                    ])->values()->all(),
                ]
            );

            return $transactions->count();
        });

        if ($updated === 0) {
            return back()->with('error', 'Tidak ada transaksi kas keluar yang dapat dihubungkan. Pastikan item belum terikat project lain.');
        }

        return back()->with('success', "{$updated} transaksi kas keluar berhasil dihubungkan ke project {$project->project_name}.");
    }

    public function attachInvoices(AttachProjectInvoicesRequest $request, Project $project): RedirectResponse
    {
        $updated = DB::transaction(function () use ($request, $project) {
            $invoices = Document::query()
                ->whereIn('id', $request->validated('invoice_ids'))
                ->where('type', 'INVOICE')
                ->whereNull('project_id')
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                return 0;
            }

            foreach ($invoices as $invoice) {
                $invoice->update(['project_id' => $project->id]);
            }

            ActivityLogger::log(
                ActivityLog::ACTION_UPDATE,
                'Project Management',
                'User '.(auth()->user()->username ?? 'Sistem')." menghubungkan {$invoices->count()} invoice ke project \"{$project->project_name}\".",
                [
                    'project_id' => $project->id,
                    'project_name' => $project->project_name,
                    'invoices' => $invoices->map(fn (Document $invoice) => [
                        'id' => $invoice->id,
                        'document_number' => $invoice->document_number,
                    ])->values()->all(),
                ]
            );

            return $invoices->count();
        });

        if ($updated === 0) {
            return back()->with('error', 'Tidak ada invoice yang dapat dihubungkan. Pastikan invoice belum terikat ke project lain.');
        }

        return back()->with('success', "{$updated} invoice berhasil dihubungkan ke project {$project->project_name}.");
    }

    public function detachInvoice(Project $project, Document $document): RedirectResponse
    {
        abort_unless($document->type === 'INVOICE', 404);

        if ((int) $document->project_id !== (int) $project->id) {
            return back()->with('error', 'Invoice ini tidak terhubung ke project tersebut.');
        }

        $document->update(['project_id' => null]);
        ActivityLogger::log(
            ActivityLog::ACTION_UPDATE,
            'Project Management',
            'User '.(auth()->user()->username ?? 'Sistem')." melepas invoice \"{$document->document_number}\" dari project \"{$project->project_name}\".",
            [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'invoice_id' => $document->id,
                'document_number' => $document->document_number,
                'detached' => true,
            ]
        );

        return back()->with('success', "Invoice {$document->document_number} berhasil dilepas dari project.");
    }

    public function detachExpense(Project $project, Transaction $transaction): RedirectResponse
    {
        abort_unless($transaction->type === 'expense', 404);

        if ((int) $transaction->project_id !== (int) $project->id) {
            return back()->with('error', 'Transaksi ini tidak terhubung ke project tersebut.');
        }

        $transaction->update(['project_id' => null]);
        ActivityLogger::log(
            ActivityLog::ACTION_UPDATE,
            'Project Management',
            'User '.(auth()->user()->username ?? 'Sistem')." melepas transaksi kas \"{$transaction->transaction_number}\" dari project \"{$project->project_name}\".",
            [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'detached' => true,
            ]
        );

        return back()->with('success', "Transaksi {$transaction->transaction_number} berhasil dilepas dari project.");
    }
}
