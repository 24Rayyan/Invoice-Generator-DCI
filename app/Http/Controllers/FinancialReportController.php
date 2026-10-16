<?php

namespace App\Http\Controllers;

use App\Exports\FinancialStatementExport;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\FinancialReportService;
use App\Services\ProjectMarginService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends Controller
{
    protected FinancialReportService $reportService;
    protected ProjectMarginService $marginService;

    public function __construct(FinancialReportService $reportService, ProjectMarginService $marginService)
    {
        $this->reportService = $reportService;
        $this->marginService = $marginService;
    }

    /**
     * Halaman Laporan Keuangan Profesional & Komprehensif
     */
    public function financialStatement(Request $request)
    {
        // Default Range: Awal bulan ini hingga hari ini / akhir bulan
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $type = $request->filled('type') ? $request->type : null;

        $reportData = $this->reportService->getDetailedFinancialReport($startDate, $endDate, $categoryId, $type);
        $setting = Setting::first();
        $categories = Category::orderBy('code', 'asc')->get();

        return view('reports.financial_statement', compact(
            'reportData',
            'setting',
            'categories',
            'startDate',
            'endDate',
            'categoryId',
            'type'
        ));
    }

    /**
     * Download Laporan Keuangan Profesional (PDF)
     */
    public function exportFinancialStatementPdf(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $type = $request->filled('type') ? $request->type : null;

        $reportData = $this->reportService->getDetailedFinancialReport($startDate, $endDate, $categoryId, $type);
        $setting = Setting::first();

        $pdf = Pdf::loadView('exports.financial_statement_pdf', compact('reportData', 'setting'))
            ->setPaper('a4', 'landscape');

        $fileName = "Laporan_Keuangan_DCI_{$startDate}_sd_{$endDate}.pdf";

        return $pdf->download($fileName);
    }

    /**
     * Download Laporan Keuangan Profesional (Excel)
     */
    public function exportFinancialStatementExcel(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $type = $request->filled('type') ? $request->type : null;

        $reportData = $this->reportService->getDetailedFinancialReport($startDate, $endDate, $categoryId, $type);
        $setting = Setting::first();

        $fileName = "Laporan_Keuangan_DCI_{$startDate}_sd_{$endDate}.xlsx";

        // Gunakan Maatwebsite Excel atau Stream Response jika class terdaftar
        try {
            return Excel::download(new FinancialStatementExport($reportData, $setting), $fileName);
        } catch (\Throwable $e) {
            // Fallback ke styled Spreadsheet (.xls)
            $fileNameXls = "Laporan_Keuangan_DCI_{$startDate}_sd_{$endDate}.xls";
            $content = view('exports.financial_statement_excel', compact('reportData', 'setting'))->render();

            return response($content, 200, [
                'Content-Type'        => 'application/vnd.ms-excel; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $fileNameXls . '"',
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ]);
        }
    }

    /**
     * Halaman Dashboard Laporan Laba Rugi Komersial & Rekonsiliasi Fiskal SPT Tahunan
     */
    public function profitAndLoss(Request $request)
    {
        $year = (int) ($request->get('year', date('Y')));
        $month = $request->filled('month') && $request->month !== 'all' ? (int) $request->month : null;

        $reportData = $this->reportService->getProfitAndLossReport($year, $month);
        $setting = Setting::first();

        // Ambil daftar tahun unik dari data transaksi
        $availableYears = Transaction::selectRaw('strftime("%Y", transaction_date) as yr')
            ->distinct()
            ->orderBy('yr', 'desc')
            ->pluck('yr')
            ->filter()
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([date('Y')]);
        }

        return view('reports.profit_loss', compact('reportData', 'setting', 'year', 'month', 'availableYears'));
    }

    /**
     * Export Laporan Laba Rugi Fiskal ke PDF Resmi (SPT Tahunan)
     */
    public function exportProfitAndLossPdf(Request $request)
    {
        $year = (int) ($request->get('year', date('Y')));
        $month = $request->filled('month') && $request->month !== 'all' ? (int) $request->month : null;

        $reportData = $this->reportService->getProfitAndLossReport($year, $month);
        $setting = Setting::first();

        $pdf = Pdf::loadView('exports.profit_loss_pdf', compact('reportData', 'setting'))
            ->setPaper('a4', 'portrait');

        $periodSlug = $month ? "Bulan_{$month}_{$year}" : "Tahun_{$year}";
        $fileName = "Laporan_Laba_Rugi_Fiskal_{$periodSlug}.pdf";

        return $pdf->download($fileName);
    }

    /**
     * Export Laporan Laba Rugi Fiskal ke Spreadsheet Excel (.xls)
     */
    public function exportProfitAndLossExcel(Request $request)
    {
        $year = (int) ($request->get('year', date('Y')));
        $month = $request->filled('month') && $request->month !== 'all' ? (int) $request->month : null;

        $reportData = $this->reportService->getProfitAndLossReport($year, $month);
        $setting = Setting::first();

        $periodSlug = $month ? "Bulan_{$month}_{$year}" : "Tahun_{$year}";
        $fileName = "Laporan_Laba_Rugi_Fiskal_{$periodSlug}.xls";

        $content = view('exports.profit_loss_excel', compact('reportData', 'setting'))->render();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ]);
    }

    /**
     * Laporan ringkas margin proyek per invoice.
     */
    public function projectMargin(Request $request)
    {
        $search = $request->get('search');
        $status = $request->filled('status') ? $request->status : null;
        $sort = $request->get('sort', 'date_desc');

        $rows = $this->marginService->overview($search, $status, $sort);
        $setting = Setting::getSetting();

        $totals = [
            'revenue'        => $rows->sum('revenue'),
            'direct_expense' => $rows->sum('direct_expense'),
            'net_profit'     => $rows->sum('net_profit'),
        ];
        $totals['net_margin'] = $totals['revenue'] > 0
            ? round(($totals['net_profit'] / $totals['revenue']) * 100, 2)
            : 0.0;

        return view('reports.project_margin', compact('rows', 'setting', 'search', 'status', 'sort', 'totals'));
    }

}
