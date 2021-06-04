<?php

namespace RZP\Excel;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RZP\Models\Payout\Bulk\Base as PayoutBulkBase;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;

class PayoutExportSheet extends ExportSheet
{
    public function array(): array
    {
        return array_merge([array_keys($this->data[0])], $this->data);
    }

    public function headings(): array
    {
        return PayoutBulkBase::EXCEL_HEADERS_FOR_PAYOUT_FILE;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Ubuntu Mono')->setSize(11);

        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('F1:M1');
        $sheet->mergeCells('N1:V1');

        $sheet->getStyle('A:V')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $sheet->getStyle('A:V')->getFont()->setName('Ubuntu Mono')->setSize(11);

        $sheet->getStyle('A1:E1')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'D9EAD3')
            )
        ));

        $sheet->getStyle('A2:E2')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'fill' => array(
                'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'D9EAD3')
            )
        ));

        $sheet->getStyle('F1:M1')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FFF2CC')
            ),
            'font' => array(
                'color' => array('rgb' => '0000EE')
            ),
        ));

        $sheet->getStyle('F2:M2')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'fill' => array(
                'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FFF2CC')
            )
        ));

        $sheet->getStyle('N1:V1')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ),
            'fill' => array(
                'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FCE5CD')
            )
        ));

        $sheet->getStyle('N2:V2')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'fill' => array(
                'fillType'  => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FCE5CD')
            )
        ));

        $sheet->getCell('F1')->getHyperlink()->setUrl("https://razorpay.com/docs/razorpayx/bulk-payouts/");

        $sheet->getStyle('F1')->getFont()->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);

        $sheet->freezePane('A3');
    }
}
