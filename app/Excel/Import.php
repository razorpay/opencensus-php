<?php

namespace RZP\Excel;

use App;
use RZP\Trace\TraceCode;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class Import extends DefaultValueBinder implements SkipsUnknownSheets, WithStartRow, WithHeadingRow, WithCustomValueBinder
{
    use Importable {
        toArray as parentToArray;
        import as parentImport;
    }

    protected $app;
    protected $headingRow = 1;
    protected $startRow = 2;

    public function __construct($startRow = 1)
    {
        $this->setStartRow($startRow);

        $this->app = App::getFacadeRoot();

        $this->app['trace']->info(TraceCode::EXCEL_CONSTRUCT_INIT,
            [
                'class' => 'Import'
            ]
        );
    }

    public function onUnknownSheet($sheetName)
    {
        // Ignore
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function setStartRow($startRow = 1)
    {
        $this->headingRow = $startRow;
        $this->startRow = $startRow + 1;

        return $this;
    }

    public function setHeadingType($headingType = 'slug')
    {
        HeadingRowFormatter::default($headingType);

        return $this;
    }

    public function setHeadingInfo($headingRow = 1, $headingType = 'slug')
    {
        $this->headingRow = $headingRow;

        HeadingRowFormatter::default($headingType);

        return $this;
    }

    public function headingRow()
    {
        return $this->headingRow;
    }

    public function import($filePath = null, string $disk = null, string $readerType = null)
    {
        $response = $this->parentImport($filePath, $disk, $readerType);

        HeadingRowFormatter::default();

        return $response;
    }

    public function toArray($filePath = null, string $disk = null, string $readerType = null): array
    {
        $data = $this->parentToArray($filePath, $disk, $readerType);

        HeadingRowFormatter::default();

        return $data;
    }
}
