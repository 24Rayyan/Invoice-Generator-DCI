<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialStatementExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    protected array $reportData;
    protected $setting;

    public function __construct(array $reportData, $setting = null)
    {
        $this->reportData = $reportData;
        $this->setting = $setting;
    }

    /**
     * Render view template for spreadsheet
     */
    public function view(): View
    {
        return view('exports.financial_statement_excel', [
            'reportData' => $this->reportData,
            'setting'    => $this->setting,
        ]);
    }

    /**
     * Set Sheet Title
     */
    public function title(): string
    {
        return 'Laporan Keuangan ' . ($this->reportData['start_date'] ?? date('Y'));
    }

    /**
     * Styling spreadsheet
     */
    public function styles(Worksheet $sheet): ?array
    {
        return [
            // Baris Judul Perusahaan
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F172A']],
                'alignment' => ['horizontal' => 'center'],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => 'center'],
            ],
            3 => [
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
