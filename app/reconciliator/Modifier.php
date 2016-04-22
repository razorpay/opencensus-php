<?php


namespace Reconciliator;
//use PHPExcel_IOFactory;
use Excel;

class Modifier
{
    const CSV_EXTENSION = 'csv';

    const MAPPINGS = [
        'No'   => 'Number',
        'Mer'  => 'Merchant',
        'Comm' => 'Commission',
        'Ac'   => 'Account',
        'Acc'  => 'Account',
        'Amt'  => 'Amount',
        'Txn'  => 'Transaction',
        'Msg'  => 'Message',
        'C'    => 'Credit',
        'D'    => 'Debit',
        'Ref'  => 'Reference',
    ];

    public function convertExcelToArray($fileDetails)
    {
        $filePath = $fileDetails['file_path'];

        $results = [];

        $results = Excel::load($filePath)->all();

        // TODO: Handle multiple sheets in a workbook
        $this->modifyColumnHeaders($results[0]->toArray());
    }

    public function modifyColumnHeaders($rows)
    {
        $columnHeaders = array_keys($rows[0]);
        //$modifiedColumnHeaders = [];

        foreach($columnHeaders as &$header)
        {
            $headerArray = explode('', $header);
        }

    }

    public function temp2()
    {
        Excel::load('file.xls', function($reader) use (&$isError) {

            $firstrow = $reader->first()->toArray();

            if (isset($firstrow['firstname']) && isset($firstrow['lastname']) && isset($firstrow['username'])) {
                $rows = $reader->all();
                foreach ($rows as $row) {
                    echo $row->firstname.' '.$row->lastname.' '.$row->username."<br />";
                }
            }
            else {
                $isError = true;

            }

        });
        if ($isError) {
            return View::make('error');
        }
    }




    public function temp1()
    {
        //--------------------------

        // Excel::load($filePath, function($reader) {
        //
        //     // ->all() is a wrapper for ->get() and will work the same
        //     $results = $reader->all();
        //
        // });

        //----------------------

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

        //-------------------------


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