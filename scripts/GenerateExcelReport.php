<?php
use App\Api\Service;

// This can be run via `php artisan tinker`.

class GenerateExcelReport
{
    protected $filePath;

    protected $file;


    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }


    public function generateExcelFile()
    {
        $data = $this->getJsonData();
        $this->file = (new Service)->generateTransactionReportAsExcel($data);
    }


    public function getJsonData()
    {
        $fileContents = file_get_contents($this->filePath);
        return json_decode($fileContents, true)["data"];
    }

    public function download()
    {
        $this->file->store('xlsx', storage_path('files'));
    }
}