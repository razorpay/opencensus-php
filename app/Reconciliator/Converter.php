<?php

namespace RZP\Reconciliator;

use Excel;
use Config;
use Trace;

use RZP\Exception;
use RZP\Trace\TraceCode;

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

    /**
     * @param array $fileDetails The excel file details
     * @param array $sheetNames The sheets that need to be collected from the file.
     *                          If empty, collects all the sheets present in the excel file.
     * @return mixed            Sheet objects retrieved from the excel file
     */
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

    public function getChunksFromExcelSheet($fileDetails, $sheetNames = [])
    {
        // TODO: Move this to constructor
        Config::set('excel.import.force_sheets_collection', false);

        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        if (empty($sheetNames) === false)
        {
            Excel::selectSheets(['Sale'])->filter('chunk')->load($filePath)->chunk(1, function($results)
            {
                Trace::getFacadeRoot()->info(
                    'RECON_INFO',
                    [
                        'message' => 'pppppp',
                        'count' => $results->count()
                    ]
                );

                var_dump($results);

                foreach ($results as $row)
                {
                    Trace::getFacadeRoot()->info(
                        'RECON_INFO',
                        [
                            'message' => 'bbbbb',
                            'type'    => get_class($row),
                            'content' => ($row),
                        ]
                    );
                }
            });
        }
        else
        {
            Excel::filter('chunk')->load($filePath)->chunk(2, function($results)
            {
                foreach($results as $sheet)
                {
                    Trace::getFacadeRoot()->info(
                        'RECON_INFO',
                        ['message'=> 'aaaaa']
                    );
                }
            });
        }
        die;
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