<?php


namespace Reconciliator;
use Excel;

class Modifier
{
    const CSV_EXTENSION = 'csv';

    const MAPPINGS = [
        'no'   => 'number',
        'mer'  => 'merchant',
        'comm' => 'commission',
        'ac'   => 'account',
        'acc'  => 'account',
        'amt'  => 'amount',
        'txn'  => 'transaction',
        'msg'  => 'message',
        'c'    => 'credit',
        'd'    => 'debit',
        'ref'  => 'reference',
    ];

    public function convertExcelToArray($fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $sheets = Excel::load($filePath)->all();

        // TODO: Handle multiple sheets in a workbook
        $rows = $sheets[0]->toArray();

        $modifiedColumnHeaders = $this->modifyColumnHeaders(array_keys($rows[0]));

        // $rows is passed by reference
        $this->replaceExcelRowsWithNewHeaders($rows, $modifiedColumnHeaders);

        return $rows;
    }

    public function convertCsvToArray($fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $columnHeaders = [];
        $data = [];

        if (($handle = fopen($filePath, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle)) !== FALSE)
            {
                if (empty($columnHeaders) === true)
                {
                    $columnHeaders = $this->modifyColumnHeaders($row);
                }
                else
                {
                    if (count($columnHeaders) !== count($row))
                    {
                        // TODO: Throw an exception about invalid column header count
                    }
                    // Combines the columnHeaders(keys) with the row(values).
                    $data[] = array_combine($columnHeaders, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }
    
    protected function replaceExcelRowsWithNewHeaders(&$rows, $newColumnHeaders)
    {
        foreach ($rows as $rowIndex=>$rowData)
        {
            if (count($newColumnHeaders) !== count(array_keys($rowData)))
            {
                // TODO: Throw an exception about invalid column header count
            }

            // Combines the columnHeaders(keys) with the rowData(values).
            $rows[$rowIndex] = array_combine($newColumnHeaders, array_values($rowData));
        }
    }

    protected function modifyColumnHeaders($columnHeaders)
    {
        //$columnHeaders = array_keys($rows[0]);
        //$modifiedColumnHeaders = [];

        // Gets the column headers without any delimiters, abbreviations.
        foreach ($columnHeaders as &$header)
        {
            // Replaces one of more spaces with underscores.
            $modifiedHeader = preg_replace('/\s+/', '_', $header);
            // Replaces multiple underscores with one underscore.
            $modifiedHeader = preg_replace('/_+/', '_', $modifiedHeader);
            // Removes dots.
            $modifiedHeader = preg_replace('/\./', '', $modifiedHeader);
            // Converts the string to lowercase.
            $modifiedHeader = strtolower($modifiedHeader);

            $headerArray = explode('_', $modifiedHeader);

            foreach($headerArray as &$substr)
            {
                if (array_key_exists($substr, self::MAPPINGS))
                {
                    $substr = self::MAPPINGS[$substr];
                }
            }
            $header = implode('',$headerArray);
        }
        
        return $columnHeaders;
    }
}