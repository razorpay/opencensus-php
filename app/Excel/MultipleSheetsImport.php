<?php

namespace RZP\Excel;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultipleSheetsImport extends Import implements WithMultipleSheets
{
    protected $sheetImports;

    public function __construct($startRow = 1, $sheets = [])
    {
        parent::__construct($startRow);

        $this->setSheets((array) $sheets);
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
