<?php


namespace Reconciliator;
//use PHPExcel_IOFactory;
use Excel;

class Modifier
{
    const CSV_EXTENSION = 'csv';

    // Default delimiter is ','. Need to hack around if we need to use a different delimiter.
    public function convertExcelToCsv($fileDetails)
    {
        











        // $filePath = $fileDetails['file_path'];
        // $csvFileName = str_replace($fileDetails['extension'], self::CSV_EXTENSION, $fileDetails['file_name']);
        // $csvFilePath = $fileDetails['destination_folder'] . '/' . $csvFileName;
        //
        // $fileType = PHPExcel_IOFactory::identify($filePath);
        // $objReader = PHPExcel_IOFactory::createReader($fileType);
        //
        // $objReader->setReadDataOnly(true);
        // $objPHPExcel = $objReader->load($filePath);
        //
        // $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
        // $objWriter->save($csvFilePath);



        // $csvFile = Excel::load($filePath, function($file) {
        // })->setFileName('abc.csv')->download('csv');
        //
        // gettype($csvFile);

        // Excel::load($filename, function($file) {
        //     // modify file content
        // })->setFileName($new_name)->store('xls');

        // $csvFileName = str_replace($fileDetails['extension'], self::CSV_EXTENSION, $fileDetails['file_name']);
        // $csvFile->move($fileDetails['destination_folder'], $csvFileName);
    }
}