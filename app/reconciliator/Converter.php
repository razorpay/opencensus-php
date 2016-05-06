<?php


namespace Reconciliator;


use Excel;

use EE\Exception;


class Converter
{
    const CSV_EXTENSION = 'csv';

    const MAPPINGS = [
        'no'   => 'number',
        'num'  => 'number',
        'mer'  => 'merchant',
        'comm' => 'commission',
        'ac'   => 'account',
        'acc'  => 'account',
        'amt'  => 'amount',
        'txn'  => 'transaction',
        'tran' => 'transaction',
        'msg'  => 'message',
        'c'    => 'credit',
        'd'    => 'debit',
        'ref'  => 'reference',
    ];

    protected $dataArray;

    public function convertFileContentToArray($fileDetails)
    {
        if ($fileDetails['file_type'] === FileProcessor::EXCEL)
        {
            $this->convertExcelToArray($fileDetails);
        }
        else if ($fileDetails['file_type'] === FileProcessor::CSV)
        {
            $this->dataArray = $this->convertCsvToArray($fileDetails);
        }
        else
        {
            throw new Exception\ReconciliationException(
                'File is neither an Excel nor a CSV type.',
                ['file_details' => $fileDetails]
            );
        }

        return $this->dataArray;
    }

    // Gets all the sheets with given set of sheet names.
    public function getAllExcelSheets($fileDetails, $sheetNames = [])
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        if (empty($sheetNames) === true)
        {
            $sheets = Excel::load($filePath)->all();
        }
        else
        {
            $sheets = Excel::selectSheets($sheetNames)->load($filePath)->all();
        }

        return $sheets;
    }


    public function getValidSheetNames($fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $validSheetNames = [];

        Excel::load($filePath, function($reader) use (&$validSheetNames)
        {
            $sheetNames = $reader->getSheetNames();

            foreach ($sheetNames as $sheetName)
            {
                // Discards all the sheets with names starting with "sheet"
                if (substr(strtolower($sheetName), 0, 5) === "sheet")
                {
                    continue;
                }

                $validSheetNames[] = $sheetName;
            }
        });

        return $validSheetNames;
    }


    public function convertExcelSheetToArray($sheet)
    {
        $rows = $sheet->toArray();
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
                    $columnHeaders = $row;
                }
                else
                {
                    if (count($columnHeaders) !== count($row))
                    {
                        throw new Exception\ReconciliationException(
                            'The number of columns in the row does not match the column headers count.',
                            ['file_details' => $fileDetails, 'column_headers' => $columnHeaders, 'row' => $row]
                        );
                    }

                    // Combines the columnHeaders(keys) with the row(values).
                    $data[] = array_combine($columnHeaders, $row);
                }
            }
            fclose($handle);
        }

        return $this->dataArray[] = $data;
    }

    protected function replaceExcelRowsWithNewHeaders(&$rows, $newColumnHeaders)
    {
        foreach ($rows as $rowIndex=>$rowData)
        {
            if (count($newColumnHeaders) !== count(array_keys($rowData)))
            {
                throw new Exception\ReconciliationException(
                    'The number of columns in the row does not match the column headers count.',
                    ['column_headers' => $newColumnHeaders, 'row' => $rowData]
                );
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