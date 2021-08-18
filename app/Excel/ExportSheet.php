<?php

namespace RZP\Excel;

use App;
use RZP\Trace\TraceCode;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;

class ExportSheet implements FromArray, WithHeadings, WithColumnFormatting, WithStrictNullComparison, ShouldAutoSize, WithStyles, WithPreCalculateFormulas, WithTitle, WithCustomStartCell
{
    protected $app;
    protected $data;
    protected $columnFormat = [];
    protected $sheetName = 'Worksheet';
    protected $autoGenerateHeading = true;
    protected $startCell = 'A1';
    protected $style = null;

    public function __construct($data, $columnFormat = [])
    {
        $this->data = $data;
        $this->columnFormat = $columnFormat;

        $this->app = App::getFacadeRoot();

        $this->app['trace']->info(TraceCode::EXCEL_CONSTRUCT_INIT,
            [
                'class' => 'ExportSheet'
            ]
        );
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        if ($this->autoGenerateHeading === true)
        {
            $firstRow = reset($this->data);

            if (is_array($firstRow) === true)
            {
                // Get the array keys
                $headings = array_keys($firstRow);

                return $headings;
            }
        }

        return [];
    }

    public function columnFormats(): array
    {
        return $this->columnFormat;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->style !== null)
        {
            call_user_func($this->style, $sheet);
        }
    }

    public function setStyle(callable $style = null)
    {
        $this->style = $style;

        return $this;
    }

    public function setTitle($sheetName)
    {
        if (empty($sheetName) === false)
        {
            $this->sheetName = $sheetName;
        }

        return $this;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function generateAutoHeading($autoGenerateHeading = true)
    {
        $this->autoGenerateHeading = $autoGenerateHeading;

        return $this;
    }

    public function startCell(): string
    {
        return $this->startCell;
    }

    public function setStartCell($startCell = 'A1')
    {
        $this->startCell = $startCell;

        return $this;
    }

    public function setColumnFormat($columnFormat = [])
    {
        $this->columnFormat = $columnFormat;

        return $this;
    }
}
