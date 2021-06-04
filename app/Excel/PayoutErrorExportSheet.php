<?php

namespace RZP\Excel;

use RZP\Models\Batch\Header;
use RZP\Models\Payout\PayoutError;
use RZP\Models\Batch\Processor\Payout;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RZP\Models\Payout\Bulk\ErrorFile as PayoutErrorFile;

class PayoutErrorExportSheet extends ExportSheet
{
    protected $containsAmazonPayFields;

    protected $mandatoryFieldsStartColumn;
    protected $mandatoryFieldsEndColumn;
    protected $conditionallyMandatoryFieldsStartColumn;
    protected $conditionallyMandatoryFieldsEndColumn;
    protected $optionalHeadersStartColumn;
    protected $optionalHeadersEndColumn;

    public function __construct($data, $columnFormat = [])
    {
        parent::__construct($data, $columnFormat);

        $firstRow = $this->data[0];

        $this->mandatoryFieldsStartColumn = 'B';

        // Set end column of mandatory fields depending on number of mandatory fields in input file
        $mandatoryColumnsCount = sizeof(array_intersect(PayoutErrorFile::MANDATORY_FIELDS_FOR_PAYOUTS_FILE, array_keys($firstRow)));
        $this->mandatoryFieldsEndColumn = chr(ord($this->mandatoryFieldsStartColumn)+$mandatoryColumnsCount-1);

        // Set end column of conditionally mandatory fields depending on number of conditionally mandatory fields in input file
        $this->conditionallyMandatoryFieldsStartColumn = chr(ord($this->mandatoryFieldsEndColumn)+1);
        $conditionallyMandatoryColumnsCount = sizeof(array_intersect(PayoutErrorFile::CONDITIONALLY_MANDATORY_FIELDS_FOR_PAYOUTS_FILE, array_keys($firstRow)));
        $this->conditionallyMandatoryFieldsEndColumn = chr(ord($this->conditionallyMandatoryFieldsStartColumn)+$conditionallyMandatoryColumnsCount-1);

        // Set end column of optional fields depending on number of optional fields in input file
        $this->optionalHeadersStartColumn = chr(ord($this->conditionallyMandatoryFieldsEndColumn)+1);
        $optionalColumnsCount = sizeof(array_intersect(PayoutErrorFile::OPTIONAL_FIELDS_FOR_PAYOUTS_FILE, array_keys($firstRow)));
        $this->optionalHeadersEndColumn = chr(ord($this->optionalHeadersStartColumn)+$optionalColumnsCount-1);
    }

    public function array(): array
    {
        return array_merge([array_keys($this->data[0])], $this->data);
    }

    public function headings(): array
    {
        // Initializing headings with '' because error file description wouldn't have
        // Mandatory, optional headers etc
        $headings = [''];
        for ($columnNumber = $this->mandatoryFieldsStartColumn; $columnNumber <= $this->mandatoryFieldsEndColumn; $columnNumber++)
        {
            array_push($headings,PayoutErrorFile::MANDATORY_FIELDS_HEADER);
        }

        for ($columnNumber = $this->conditionallyMandatoryFieldsStartColumn; $columnNumber <= $this->conditionallyMandatoryFieldsEndColumn; $columnNumber++)
        {
            array_push($headings, PayoutErrorFile::CONDITIONALLY_MANDATORY_FIELDS_HEADER);
        }

        for ($columnNumber = $this->optionalHeadersStartColumn; $columnNumber <= $this->optionalHeadersEndColumn; $columnNumber++)
        {
            array_push($headings, PayoutErrorFile::OPTIONAL_FIELDS_HEADER);
        }

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Ubuntu Mono')->setSize(11);

        $sheet->mergeCells($this->mandatoryFieldsStartColumn.'1:'.$this->mandatoryFieldsEndColumn.'1');
        $sheet->mergeCells($this->conditionallyMandatoryFieldsStartColumn.'1:'.$this->conditionallyMandatoryFieldsEndColumn.'1');
        $sheet->mergeCells($this->optionalHeadersStartColumn.'1:'.$this->optionalHeadersEndColumn.'1');

        $sheet->getStyle('A:'.$this->optionalHeadersEndColumn)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $sheet->getStyle('A:'.$this->optionalHeadersEndColumn)->getFont()->setName('Ubuntu Mono')->setSize(11);

        $sheet->getStyle($this->mandatoryFieldsStartColumn.'1:'.$this->mandatoryFieldsEndColumn.'1')->applyFromArray(array(
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

        $sheet->getStyle($this->mandatoryFieldsStartColumn.'2:'.$this->mandatoryFieldsEndColumn.'2')->applyFromArray(array(
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

        $sheet->getStyle($this->conditionallyMandatoryFieldsStartColumn.'1:'.$this->conditionallyMandatoryFieldsEndColumn.'1')->applyFromArray(array(
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

        $sheet->getStyle($this->conditionallyMandatoryFieldsStartColumn.'2:'.$this->conditionallyMandatoryFieldsEndColumn.'2')->applyFromArray(array(
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

        $sheet->getStyle($this->optionalHeadersStartColumn.'1:'.$this->optionalHeadersEndColumn.'1')->applyFromArray(array(
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

        $sheet->getStyle($this->optionalHeadersStartColumn.'2:'.$this->optionalHeadersEndColumn.'2')->applyFromArray(array(
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

        $sheet->getCell($this->conditionallyMandatoryFieldsStartColumn.'1')->getHyperlink()->setUrl("https://razorpay.com/docs/razorpayx/bulk-payouts/");

        $sheet->getStyle($this->conditionallyMandatoryFieldsStartColumn.'1')->getFont()->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);

        $sheet->freezePane('A3');
    }
}
