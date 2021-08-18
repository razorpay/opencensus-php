<?php

namespace RZP\Excel;

use App;
use RZP\Trace\TraceCode;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultipleSheetsImport extends Import implements WithMultipleSheets
{
    protected $app;
    protected $sheetImports;

    public function __construct($startRow = 1, $sheets = [])
    {
        parent::__construct($startRow);

        $this->setSheets((array) $sheets);

        $this->app = App::getFacadeRoot();

        $this->app['trace']->info(TraceCode::TRACE_REQUEST_METRIC,
            [
                'class' => 'MultipleSheetsImport'
            ]
        );
    }

    public function setSheets($sheets)
    {
        $sheets = is_array($sheets) ? $sheets : func_get_args();

        $this->sheetImports = array_fill_keys($sheets, $this);

        return $this;
    }

    public function sheets(): array
    {
        return $this->sheetImports;
    }
}
