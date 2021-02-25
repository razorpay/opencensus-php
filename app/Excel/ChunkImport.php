<?php

namespace RZP\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;

class ChunkImport extends MultipleSheetsImport implements WithChunkReading, WithEvents, ToCollection
{
    use RegistersEventListeners;

    protected $chunkSize = 10000;
    protected $chunkCallback = null;

    public function import($filePath = null, string $disk = null, string $readerType = null)
    {
        $this->filePath = $filePath;

        return parent::import($filePath, $disk, $readerType);
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
