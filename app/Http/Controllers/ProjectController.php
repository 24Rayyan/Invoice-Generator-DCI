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
use App\Services\ProjectMarginService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::with('client')
            ->withSum('invoices', 'total_amount')
            ->withSum([
                'directExpenses as direct_expenses_sum_amount' => fn ($query) => $query->whereNull('transactions.invoice_id'),
            ], 'project_transaction_allocations.allocated_amount')
            ->withSum('invoiceExpenses as invoice_expenses_sum_amount', 'amount')
            ->orderByDesc('created_at')
            ->paginate(15);
        $projects->getCollection()->transform(function (Project $project) {
            $revenue = (float) ($project->invoices_sum_total_amount ?? 0);
            $expenses = (float) ($project->direct_expenses_sum_amount ?? 0)
                + (float) ($project->invoice_expenses_sum_amount ?? 0);
            $project->setAttribute('net_profit', $revenue - $expenses);

            return $project;
        });
        $customers = Customer::orderBy('name')->get();
        $unallocatedInvoices = Document::query()
            ->with('customer')
            ->withSum('directExpenses', 'amount')
            ->where('type', 'INVOICE')
            ->whereNull('project_id')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
        $requestedInvoiceId = request()->integer('invoice_id');
        $selectedInvoice = $unallocatedInvoices->firstWhere('id', $requestedInvoiceId);
        $selectedInvoiceIds = $selectedInvoice ? [$selectedInvoice->id] : [];
        $selectedClientId = $selectedInvoice?->customer_id;

        if ($selectedInvoice) {
            $unallocatedInvoices = $unallocatedInvoices->prepend($selectedInvoice)->unique('id')->values();
        }

        $setting = Setting::getSetting();
        $currency = $setting->currency_symbol ?? 'Rp';

        return view('projects.index', compact(
            'projects',
            'customers',
            'unallocatedInvoices',
            'selectedInvoiceIds',
            'selectedClientId',
            'currency'
        ));
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $invoiceIds = $validated['invoice_ids'] ?? [];
        unset($validated['invoice_ids']);

        [$project, $invoiceCount] = DB::transaction(function () use ($validated, $invoiceIds) {
            $project = Project::create($validated);

            if ($invoiceIds === []) {
                return [$project, 0];
            }

            $invoices = $this->unallocatedInvoicesByIds($invoiceIds);

            if ($invoices->count() !== count($invoiceIds)) {
                throw ValidationException::withMessages([
                    'invoice_ids' => 'Beberapa invoice sudah tidak tersedia atau sudah terhubung ke proyek lain. Muat ulang halaman dan pilih kembali.',
                ]);
            }

            return [$project, $this->linkInvoicesToProject($project, $invoices)];
        });

        $message = $invoiceCount > 0
            ? "Project berhasil dibuat dan {$invoiceCount} invoice langsung dihubungkan."
            : 'Project berhasil dibuat.';

        return redirect()->route('projects.index')->with('success', $message);
    }

    public function show(Project $project, ProjectMarginService $marginService): View
    {
        $project->load([
            'client',
            'invoices.customer',
            'directExpenses.category',
            'invoiceExpenses.category',
            'invoiceExpenses.invoice',
        ]);
        $setting = Setting::getSetting();
        $customers = Customer::orderBy('name')->get();
        $revenue = (float) $project->invoices->sum('total_amount');
        $invoiceLinkedExpenses = $project->invoiceExpenses;
        $directProjectExpenses = $project->directExpenses
            ->reject(fn (Transaction $transaction) => $transaction->invoice_id !== null)
            ->values();
        $directExpense = (float) $directProjectExpenses->sum(fn (Transaction $transaction) => $transaction->pivot->allocated_amount)
            + (float) $invoiceLinkedExpenses->sum('amount');
        $projectExpenses = $directProjectExpenses
            ->map(fn (Transaction $transaction) => [
                'transaction' => $transaction,
                'amount' => (float) $transaction->pivot->allocated_amount,
                'invoice' => null,
            ])
            ->concat($invoiceLinkedExpenses->map(fn (Transaction $transaction) => [
                'transaction' => $transaction,
                'amount' => (float) $transaction->amount,
                'invoice' => $transaction->invoice,
            ]))
            ->sortByDesc(fn (array $expense) => $expense['transaction']->transaction_date?->timestamp ?? 0)
            ->values();
        $netProfit = $revenue - $directExpense;
        $netMargin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0.0;
        $unallocatedExpenses = $marginService->unallocatedProjectExpenses();
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
            'projectExpenses',
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
        $result = DB::transaction(function () use ($request, $project) {
            $transactionIds = $request->validated('transaction_ids');
            $transactions = Transaction::query()
                ->whereIn('id', $transactionIds)
                ->where('type', 'expense')
                ->whereNull('project_id')
                ->whereNull('invoice_id')
                ->lockForUpdate()
                ->get();

            if ($transactions->count() !== count($transactionIds)) {
                return 0;
            }

            $allocations = [];
            foreach ($transactions as $transaction) {
                $requestedAmount = $request->validated("allocations.{$transaction->id}");
                $alreadyAllocatedCents = $this->amountInCents(
                    $transaction->projects()->sum('project_transaction_allocations.allocated_amount')
                );
                $transactionAmountCents = $this->amountInCents($transaction->amount);
                $remainingCents = $transactionAmountCents - $alreadyAllocatedCents;
                $allocationCents = $requestedAmount === null || $requestedAmount === ''
                    ? $remainingCents
                    : $this->amountInCents($requestedAmount);

                if ($remainingCents <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "allocations.{$transaction->id}" => 'Transaksi ini sudah tidak memiliki sisa yang dapat dialokasikan.',
                    ]);
                }

                if ($allocationCents > $remainingCents) {
                    $remainingAmount = number_format(max(0, $remainingCents) / 100, 2, ',', '.');

                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "allocations.{$transaction->id}" => "Nominal melebihi sisa transaksi. Sisa yang dapat dialokasikan: Rp {$remainingAmount}.",
                    ]);
                }

                $currentProjectAmount = $transaction->projects()
                    ->whereKey($project->id)
                    ->value('project_transaction_allocations.allocated_amount');
                $allocations[] = [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'allocated_amount' => number_format($allocationCents / 100, 2, '.', ''),
                    'project_total' => number_format(($this->amountInCents($currentProjectAmount ?? 0) + $allocationCents) / 100, 2, '.', ''),
                ];

                $transaction->projects()->syncWithoutDetaching([
                    $project->id => [
                        'allocated_amount' => ($this->amountInCents($currentProjectAmount ?? 0) + $allocationCents) / 100,
                    ],
                ]);
            }

            ActivityLogger::logForModel(
                ActivityLog::ACTION_ATTACH,
                'Project Management',
                'User '.(auth()->user()->username ?? 'Sistem')." menghubungkan {$transactions->count()} transaksi kas ke project \"{$project->project_name}\".",
                $project,
                [
                    'project_id' => $project->id,
                    'project_name' => $project->project_name,
                    'transactions' => $allocations,
                ]
            );

            return $transactions->count();
        });

        if ($result === 0) {
            return back()->withInput()->with('error', 'Beberapa transaksi sudah tidak tersedia. Muat ulang halaman dan coba lagi.');
        }

        return back()->with('success', "{$result} transaksi kas keluar berhasil dialokasikan ke project {$project->project_name}.");
    }

    public function attachInvoices(AttachProjectInvoicesRequest $request, Project $project): RedirectResponse
    {
        $updated = DB::transaction(function () use ($request, $project) {
            $invoices = $this->unallocatedInvoicesByIds($request->validated('invoice_ids'));

            if ($invoices->isEmpty()) {
                return 0;
            }

            return $this->linkInvoicesToProject($project, $invoices);
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

        $document->updateQuietly(['project_id' => null]);
        ActivityLogger::logForModel(
            ActivityLog::ACTION_DETACH,
            'Project Management',
            'User '.(auth()->user()->username ?? 'Sistem')." melepas invoice \"{$document->document_number}\" dari project \"{$project->project_name}\".",
            $document,
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

        $allocatedAmount = DB::transaction(function () use ($project, $transaction) {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();
            $amount = $lockedTransaction->projects()
                ->whereKey($project->id)
                ->value('project_transaction_allocations.allocated_amount');

            if ($amount !== null) {
                $lockedTransaction->projects()->detach($project->id);
            }

            return $amount;
        });

        if ($allocatedAmount === null) {
            return back()->with('error', 'Transaksi ini tidak terhubung ke project tersebut.');
        }

        ActivityLogger::logForModel(
            ActivityLog::ACTION_DETACH,
            'Project Management',
            'User '.(auth()->user()->username ?? 'Sistem')." melepas transaksi kas \"{$transaction->transaction_number}\" dari project \"{$project->project_name}\".",
            $project,
            [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'allocated_amount' => $allocatedAmount,
                'detached' => true,
            ]
        );

        return back()->with('success', "Alokasi transaksi {$transaction->transaction_number} berhasil dilepas dari project.");
    }

    private function amountInCents($amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function unallocatedInvoicesByIds(array $invoiceIds): EloquentCollection
    {
        return Document::query()
            ->whereIn('id', $invoiceIds)
            ->where('type', 'INVOICE')
            ->whereNull('project_id')
            ->lockForUpdate()
            ->get();
    }

    private function linkInvoicesToProject(Project $project, EloquentCollection $invoices): int
    {
        foreach ($invoices as $invoice) {
            $invoice->updateQuietly(['project_id' => $project->id]);
        }

        ActivityLogger::logForModel(
            ActivityLog::ACTION_ATTACH,
            'Project Management',
            'User '.(auth()->user()->username ?? 'Sistem')." menghubungkan {$invoices->count()} invoice ke project \"{$project->project_name}\".",
            $project,
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
    }
}
