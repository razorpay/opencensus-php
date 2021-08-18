<?php

namespace RZP\Excel;

use App;
use RZP\Trace\TraceCode;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Export implements WithMultipleSheets
{
    use Exportable;

    protected $app;
    protected $data;
    protected $columnFormat;
    protected $sheetNames;
    protected $autoGenerateHeading = true;
    protected $sheets = [];

    public function __construct($data = [], $columnFormat = [], $sheetNames = [], $sheetExport = ExportSheet::class)
    {
        $this->data = $data;
        $this->columnFormat = $columnFormat;
        $this->sheetNames = $sheetNames;
        $this->sheetExport = $sheetExport;

        $this->app = App::getFacadeRoot();

        $this->app['trace']->info(TraceCode::EXCEL_CONSTRUCT_INIT,
            [
                'class' => 'Export'
            ]
        );
    }

    public function setSheets(callable $closure)
    {
        $this->sheets = $closure();

        return $this;
    }

    public function sheets(): array
    {
        if (empty($this->sheets) === false)
        {
            return $this->sheets;
        }

        $sheet = [];

        foreach ($this->sheetNames as $sheetName)
        {
            $data = $this->data[$sheetName] ?? $this->data;

            $sheet[$sheetName] = (new $this->sheetExport($data, $this->columnFormat))->setTitle($sheetName)->generateAutoHeading($this->autoGenerateHeading);
        }

        return $sheet;
    }

    public function generateAutoHeading($autoGenerateHeading = true)
    {
        $this->autoGenerateHeading = $autoGenerateHeading;

        return $this;
    }
}
