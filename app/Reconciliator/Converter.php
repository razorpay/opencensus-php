<?php

namespace RZP\Reconciliator;

use Excel;
use Config;

use RZP\Exception;

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

    const MAX_SHEETS_ALLOWED = 10;
    const ROW_CHUNK_SIZE = 500;

    protected $dataArray;

    /**
     * @param array $fileDetails The excel file details
     * @param array $sheetNames The sheets that need to be collected from the file.
     *                          If empty, collects all the sheets present in the excel file.
     * @return mixed            Sheet objects retrieved from the excel file
     */
    public function getAllExcelSheets($fileDetails, $sheetNames = [])
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        Config::set('excel.import.force_sheets_collection', true);

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

    public function getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames = [])
    {
        //
        // For the current implementation to work the way it is expected to,
        // force_sheets_collection MUST be set to false. We are loading sheet
        // by sheet in this particular implementation and hence would want
        // an array of rows to be returned rather than an array of sheets.
        //
        Config::set('excel.import.force_sheets_collection', false);

        if (empty($sheetNames) === false)
        {
            return $this->getRowsFromExcelSheetsOptimizedWithSheetNames($fileDetails, $sheetNames);
        }

        return $this->getRowsFromExcelSheetsOptimizedWithSheetIndices($fileDetails);
    }

    /**
     * If this function is being used, ensure that the sheet name is not being
     * used to perform any operations in the core reconciliation flow. Since, this
     * function does not get any sheet name at all.
     *
     * @param array $fileDetails
     * @return array
     */
    protected function getRowsFromExcelSheetsOptimizedWithSheetIndices(array $fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $allSheetsContent = [];

        foreach (range(0, self::MAX_SHEETS_ALLOWED) as $index)
        {
            $randomSheetName = '';
            $allSheetsContent[$randomSheetName] = [];

            Excel::filter('chunk')->selectSheetsByIndex($index)->load($filePath)->chunk(
                self::ROW_CHUNK_SIZE,
                function ($results) use ($randomSheetName, & $allSheetsContent)
                {
                    foreach ($results as $row)
                    {
                        // Currently, since it returns an array of rows, there's no
                        // way to get the sheet names. And we cannot let it return
                        // an array of sheets because chunk works only on a
                        // cell collection (rows) and not on a row collection (sheets)
                        $allSheetsContent[$randomSheetName][] = $row;
                    }
                },
                false
            );
        }

        return $allSheetsContent;
    }

    protected function getRowsFromExcelSheetsOptimizedWithSheetNames(array $fileDetails, array $sheetNames)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $allSheetsContent = [];

        foreach ($sheetNames as $sheetName)
        {
            $allSheetsContent[$sheetName] = [];

            try
            {
                Excel::filter('chunk')->selectSheets($sheetName)->load($filePath)->chunk(
                    self::ROW_CHUNK_SIZE,
                    function ($results) use ($sheetName, & $allSheetsContent)
                    {
                        foreach ($results as $row)
                        {
                            $allSheetsContent[$sheetName][] = $row;
                        }
                    },
                    false
                );
            }
            catch (\Exception $ex)
            {
                //
                // This exception with the below message is thrown when the particular sheet
                // is not found in the excel file. The reason we let this be is because maatwebsite
                // does not fail silently if the given sheet does not exist. We have
                // a possible list of sheets that can be present in the given file, hardcoded
                // on which we run this code block.
                //
                
                if (strpos(strtolower($ex->getMessage()), 'undefined variable: index') !== false)
                {
                    continue;
                }

                throw $ex;
            }
        }

        return $allSheetsContent;
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

        $handle = fopen($filePath, 'r');

        if ($handle === false)
        {
            throw new Exception\RuntimeException(
                'Unable to open file . ' . $filePath);
        }

        try
        {
            $columnHeadersCount = 0;

            while (($row = fgetcsv($handle)) !== false)
            {
                // If headers are empty, get headers from the first row.
                if (empty($columnHeaders) === true)
                {
                    $columnHeaders = array_map('trim', $row);
                    $columnHeadersCount = count($columnHeaders);
                }
                else
                {
                    if ($columnHeadersCount !== count($row))
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
        }
        finally
        {
            fclose($handle);
        }

        return $data;
    }
}