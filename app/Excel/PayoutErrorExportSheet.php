<?php

namespace RZP\Excel;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RZP\Models\Payout\Bulk\ErrorFile as PayoutErrorFile;

class PayoutErrorExportSheet extends ExportSheet
{
    public function array(): array
    {
        return array_merge([array_keys($this->data[0])], $this->data);
    }

    public function headings(): array
    {
        return PayoutErrorFile::EXCEL_HEADERS_FOR_PAYOUT_FILE;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Ubuntu Mono')->setSize(11);

        $sheet->mergeCells('B1:F1');
        $sheet->mergeCells('G1:M1');
        $sheet->mergeCells('N1:U1');

        $sheet->getStyle('A:V')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $sheet->getStyle('A:V')->getFont()->setName('Ubuntu Mono')->setSize(11);

        $sheet->getStyle('B1:F1')->applyFromArray(array(
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
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'D9EAD3')
            )
        ));

        $sheet->getStyle('B2:F2')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'fill' => array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'D9EAD3')
            )
        ));

        $sheet->getStyle('G1:M1')->applyFromArray(array(
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
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FFF2CC')
            ),
            'font' => array(
                'color' => array('rgb' => '0000EE')
            ),
        ));

        $sheet->getStyle('G2:M2')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'fill' => array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FFF2CC')
            )
        ));

        $sheet->getStyle('N1:U1')->applyFromArray(array(
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
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FCE5CD')
            )
        ));

        $sheet->getStyle('N2:U2')->applyFromArray(array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('rbg' => '000000')
                ),
            ),
            'fill' => array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'FCE5CD')
            )
        ));

        $sheet->getCell('G1')->getHyperlink()->setUrl("https://razorpay.com/docs/razorpayx/bulk-payouts/");

        $sheet->getStyle('G1')->getFont()->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);

        $sheet->freezePane('A3');
    }
}
