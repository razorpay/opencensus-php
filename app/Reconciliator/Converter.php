<?php

namespace RZP\Reconciliator;

use Str;
use Excel;
use Config;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\FileStore\Format;

class Converter
{
    const DEFAULT_DELIMITER = ',';

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

    const MAX_SHEETS_ALLOWED = 3;
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

    /**
     * Convers excel sheet to in memory array, by using spout or maatwebsite excel parser
     * depending on the extension of the excel file
     *
     * @param  array  $fileDetails details of the file being processed
     * @param  array  $sheetNames  sheet names to be considered
     * @param  int    $startRow
     */
    public function convertExcelToArray(array $fileDetails, $sheetNames, int $startRow)
    {
        if ($this->shouldUseSpoutLib($fileDetails[FileProcessor::EXTENSION]) === true)
        {
            // getting contents using spout library for xlsx
            $sheetsContents = $this->getRowsFromExcelSheetsSpout($fileDetails, $sheetNames);
        }
        else
        {
            $sheetsContents = $this->getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames, $startRow);
        }

        $fileContents = [];

        foreach ($sheetsContents as $sheetName => $rows)
        {
            if (empty($rows) === true)
            {
                // This would happen when the sheet name sent, does not exist
                continue;
            }

            $sheetArray = [];

            $sheetArray = array_merge($sheetArray, $rows);

            // foreach ($rows as $cellCollection)
            // {
            //     if ($this->shouldUseSpoutLib($fileDetails[FileProcessor::EXTENSION]) === true)
            //     {
            //         $sheetArray[] = $cellCollection;
            //     }
            //     else
            //     {
            //         $sheetArray[] = $cellCollection->all();
            //     }
            // }

            $fileContents[$sheetName] = $sheetArray;
        }

        return $fileContents;
    }

    public function getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames = [], $startRow = 1)
    {
        //
        // For the current implementation to work the way it is expected to,
        // force_sheets_collection MUST be set to false. We are loading sheet
        // by sheet in this particular implementation and hence would want
        // an array of rows to be returned rather than an array of sheets.
        //
        Config::set('excel.import.force_sheets_collection', false);

        Config::set('excel.import.startRow', $startRow);

        if (empty($sheetNames) === false)
        {
            return $this->getRowsFromExcelSheetsOptimizedWithSheetNames($fileDetails, $sheetNames);
        }

        return $this->getRowsFromExcelSheetsOptimizedWithSheetIndices($fileDetails);
    }

    /**
     * This function uses Spout library to read data from Excel sheets
     *
     * @param $fileDetails
     * @param $sheetNames
     * @return array excel sheet content of mentioned file
     */
    public function getRowsFromExcelSheetsSpout($fileDetails, $sheetNames = [])
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $reader = ReaderFactory::create(Type::XLSX);
        $reader->setShouldPreserveEmptyRows(false);
        $reader->setShouldFormatDates(true);
        $reader->open($filePath);

        if (empty($sheetNames) === false)
        {
            return $this->getRowsFromExcelSheetsWithSheetNamesSpout($reader, $sheetNames);
        }

        return $this->getRowsFromExcelSheetsWithIndicesSpout($reader);
    }

    public function convertExcelSheetToArray($sheet)
    {
        $rows = $sheet->toArray();

        return $rows;
    }

    public function convertCsvToArray(
        $fileDetails,
        $columnHeaders = [],
        array $linesToSkip = [],
        $delimiter = ',')
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $data = [];

        $columnHeadersCount = count($columnHeaders);

        $totalLinesToRead = $this->getTotalLinesToRead($filePath, $linesToSkip);
        $linesToSkipFromTop = $linesToSkip[FileProcessor::LINES_FROM_TOP] ?? 0;
        $currentLineNumber = 0;

        $handle = fopen($filePath, 'r');

        if ($handle === false)
        {
            throw new Exception\RuntimeException(
                'Unable to open file . ' . $filePath);
        }

        try
        {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
            {
                //
                // Skip the first few ($linesToSkipFromTop) rows
                // Or jump right over it if it's an empty row.
                //
                if (($currentLineNumber < $linesToSkipFromTop) or
                    (empty(array_filter($row))))
                {
                    $currentLineNumber++;

                    continue;
                }

                // Skip the last few ($totalLinesToRead) rows
                if (($totalLinesToRead !== null) and
                    ($currentLineNumber >= $totalLinesToRead))
                {
                    break;
                }

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

                $currentLineNumber++;
            }
        }
        finally
        {
            fclose($handle);
        }

        return $data;
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
            $randomSheetName = 'sheet' . $index;
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
                        $allSheetsContent[$randomSheetName][] = $row->all();
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
                            $allSheetsContent[$sheetName][] = $row->all();
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

                $exceptionMessage = strtolower($ex->getMessage());

                if ((strpos($exceptionMessage, 'undefined variable: index') !== false) or
                    (strpos($exceptionMessage, 'the actual number of sheets is 0') !== false))
                {
                    continue;
                }

                throw $ex;
            }
        }

        return $allSheetsContent;
    }

    protected function shouldUseSpoutLib(string $extension): bool
    {
        return ($extension === Format::XLSX);
    }

    protected function getRowsFromExcelSheetsWithIndicesSpout($reader)
    {
        $allSheetsContent = [];

        foreach ($reader->getSheetIterator() as $sheet)
        {
            $index = $sheet->getIndex();

            $sheetName = 'sheet' . $index;

            $this->setSheetContentForSpout($allSheetsContent, $sheet, $sheetName);
        }

        return $allSheetsContent;
    }

    protected function getRowsFromExcelSheetsWithSheetNamesSpout($reader, array $sheetNames)
    {
        $allSheetsContent = [];

        foreach ($reader->getSheetIterator() as $sheet)
        {
            $sheetName = $sheet->getName();

            if (in_array($sheetName, $sheetNames, true) === false)
            {
                continue;
            }

            $this->setSheetContentForSpout($allSheetsContent, $sheet, $sheetName);
        }

        return $allSheetsContent;
    }

    protected function setSheetContentForSpout(array & $allSheetsContent, $sheet, string $sheetName)
    {
        $sheetHeaders = [];

        $allSheetsContent[$sheetName] = [];

        $rowIterator = $sheet->getRowIterator();

        foreach ($rowIterator as $row)
        {
            // this deals with the empty rows
            if (count(array_filter($row)) === 0)
            {
                continue;
            }

            if ($rowIterator->key() === 1)
            {
                $sheetHeaders = $this->normalizeHeaders($row);
            }
            else
            {
                if (count($sheetHeaders) === count($row))
                {
                    $allSheetsContent[$sheetName][] = array_combine($sheetHeaders, $row);
                }
                // breaking case when header count is not same as row.
                else
                {
                    (new Messenger)->raiseReconAlert(
                        [
                            'trace_code'   => TraceCode::RECON_ALERT,
                            'message'      => 'The number of columns in the row does not match the column headers count',
                            'file_details' => ['column_headers' => $sheetHeaders, 'row' => $row],
                        ]);

                    continue;
                }
            }
        }
    }

    protected function getTotalLinesToRead(string $filePath, array $linesToSkip)
    {
        $totalLinesToRead = null;

        $linesFromBottom = $linesToSkip[FileProcessor::LINES_FROM_BOTTOM] ?? 0;

        if ($linesFromBottom > 0)
        {
            // Loads the file into memory to get the number of lines to read
            $fileContent = file($filePath);
            $fileLinesCount = count($fileContent);

            $totalLinesToRead = $fileLinesCount - $linesFromBottom;
        }

        return $totalLinesToRead;
    }

    /**
     * Normalizes the header values of excel
     * converts ascii to string
     * converts to snake case
     * eg : 'Merch @ Rpting Lvl' -> 'merch_at_rpting_level'
     *      'Card Name' -> 'card_name'
     * ref : https://github.com/Maatwebsite/Laravel-Excel/blob/2.1/src/Maatwebsite/Excel/Parsers/ExcelParser.php#L284
     *
     * @param $headers array
     * @return array
     */
    protected function normalizeHeaders(array $headers)
    {
        $normalized = [];

        $separator = '_';

        foreach ($headers as $val)
        {
            // check if string has ascii text
            // convert it into string
            if (mb_check_encoding($val, 'ASCII') === true)
            {
                $val = Str::ascii($val);
            }

            // Convert all dashes/underscores into separator
            $flip = $separator === '-' ? '_' : '-';
            $val = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $val);

            // Remove all characters that are not the separator,
            // letters, numbers, or whitespace.
            $val = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', mb_strtolower($val));

            // Replace all separator characters and whitespace by a single separator
            $val = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $val);
            $val = trim($val, $separator);

            array_push($normalized, $val);
        }

        return $normalized;
    }
}
