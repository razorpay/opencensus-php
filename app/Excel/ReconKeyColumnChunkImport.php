<?php

namespace RZP\Excel;

use App;
use RZP\Trace\TraceCode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class ReconKeyColumnChunkImport extends DefaultValueBinder implements SkipsUnknownSheets, WithStartRow, WithCustomValueBinder, WithMultipleSheets, WithChunkReading, WithEvents, ToCollection
{
    protected $app;

    use RegistersEventListeners;

    use Importable {
        import as parentImport;
    }

    protected $startRow;
    protected $chunkSize = 10000;
    protected $chunkCallback = null;
    protected $sheetImports = [];

    public function __construct($startRow = 1, $sheets = [])
    {
        $this->startRow = $startRow;

        $this->setSheets((array) $sheets);

        $this->app = App::getFacadeRoot();

        $this->app['trace']->info(TraceCode::TRACE_REQUEST_METRIC,
            [
                'class' => 'ReconKeyColumnChunkImport'
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
        $this->startRow = $startRow;

        return $this;
    }

    public function import($filePath = null, string $disk = null, string $readerType = null)
    {
        $this->filePath = $filePath;

        return $this->parentImport($filePath, $disk, $readerType);
    }

    /**
     * Unsetting sheetnames from sheetimports to only
     * process those sheets which we know are present
     * in the file, will throw error if not done so.
     *
     * @param BeforeImport $event
     */
    public static function beforeImport(BeforeImport $event)
    {
        $sheetNames = $event->reader->getPhpSpreadsheetReader()->listWorksheetNames($event->getConcernable()->getFilePath());
        $sheetImports = $event->getConcernable()->sheets();

        foreach ($sheetImports as $sheetName => $import)
        {
            if ((is_numeric($sheetName) and
                (isset($sheetNames[$sheetName]) === false)))
            {
                unset($sheetImports[$sheetName]);
            }
            else if (in_array($sheetName, $sheetNames) === false)
            {
                unset($sheetImports[$sheetName]);
            }
        }

        $event->getConcernable()->setSheets(array_keys($sheetImports));
    }

    public function setChunk($chunkSize = 1000, callable $callback)
    {
        $this->chunkSize = $chunkSize;
        $this->chunkCallback = $callback;

        return $this;
    }

    public function chunkSize(): int
    {
        return $this->chunkSize;
    }

    public function collection(Collection $collection)
    {
        call_user_func($this->chunkCallback, $collection);
    }

    private function getFilePath()
    {
        return $this->filePath;
    }
}
