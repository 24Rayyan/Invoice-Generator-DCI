<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;

class ProjectMarginService
{
    /**
     * Ringkasan margin satu invoice: pendapatan, kas keluar terikat, laba, dan persen margin.
     */
    public function forInvoice(Document $invoice): array
    {
        $revenue = (float) $invoice->total_amount;
        $directExpense = (float) ($invoice->direct_expenses_sum_amount
            ?? $invoice->directExpenses()->sum('amount'));

        return $this->compose($invoice, $revenue, $directExpense);
    }

    /**
     * Ringkasan margin seluruh invoice (opsional filter & sort) untuk laporan proyek.
     */
    public function overview(?string $search = null, ?string $status = null, string $sort = 'date_desc'): Collection
    {
        $query = Document::query()
            ->with('customer')
            ->withSum('directExpenses', 'amount')
            ->where('type', 'INVOICE');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $rows = $query->orderByDesc('date')->orderByDesc('id')->get()
            ->map(function (Document $invoice) {
                $revenue = (float) $invoice->total_amount;
                $directExpense = (float) ($invoice->direct_expenses_sum_amount ?? 0);

                return $this->compose($invoice, $revenue, $directExpense);
            });

        return $this->sortRows($rows, $sort);
    }

    protected function compose(Document $invoice, float $revenue, float $directExpense): array
    {
        $netProfit = $revenue - $directExpense;
        $netMargin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0.0;

        return [
            'invoice'        => $invoice,
            'revenue'        => $revenue,
            'direct_expense' => $directExpense,
            'net_profit'     => $netProfit,
            'net_margin'     => $netMargin,
        ];
    }

    protected function sortRows(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'margin_asc'   => $rows->sortBy('net_margin')->values(),
            'margin_desc'  => $rows->sortByDesc('net_margin')->values(),
            'profit_asc'   => $rows->sortBy('net_profit')->values(),
            'profit_desc'  => $rows->sortByDesc('net_profit')->values(),
            'revenue_asc'  => $rows->sortBy('revenue')->values(),
            'revenue_desc' => $rows->sortByDesc('revenue')->values(),
            'date_asc'     => $rows->sortBy(fn ($row) => optional($row['invoice']->date)->timestamp ?? 0)->values(),
            default        => $rows->sortByDesc(fn ($row) => optional($row['invoice']->date)->timestamp ?? 0)->values(),
        };
    }
};
