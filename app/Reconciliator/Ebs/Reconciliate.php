<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Reconciliator\Converter;

use PHPExcel_IOFactory;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function writeXlsToXlsx($fileDetails)
    {
        $dir = storage_path('files/settlement');

        if (file_exists($dir) === false)
        {
            mkdir($dir);
        }

        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $fileName = $fileDetails[FileProcessor::FILE_NAME];

        $newFileName = explode('.', $fileName)[0];

        $newFilePath = $dir . '/' . $newFileName . '.csv';

        // Create a reader to read .xls format
        $reader = PHPExcel_IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        // Read the .xls file from upload storage
        $workbook = $reader->load($filePath, 'utf8');

        // Create a writer to output in .xlsx format
        $writer = PHPExcel_IOFactory::createWriter($workbook, 'CSV');
        // Save file to destination .xlsx path
        $writer->save($newFilePath);
        sd($newFilePath);
        sd($filePath);

        // $converter = new Converter;

        // $data = $converter->getRowsFromExcelSheetsSpout($filePath);

        // sd($data);

    }
}
