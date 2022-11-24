<?php

namespace RZP\Reconciliator;

use Str;
use Excel;
use Config;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Excel\ChunkImport;
use RZP\Models\FileStore\Format;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Excel\ReconKeyColumnChunkImport;

class Converter extends Base\Core
{
    const DEFAULT_DELIMITER = ',';

    const NORMALIZED_HEADER_GATEWAYS = [
        RequestProcessor\Base::ATOM,
        RequestProcessor\Base::HDFC,
        RequestProcessor\Base::MPESA,
        RequestProcessor\Base::AIRTEL,
        RequestProcessor\Base::PAYZAPP,
        RequestProcessor\Base::BILLDESK,
        RequestProcessor\Base::MOBIKWIK,
        RequestProcessor\Base::JIOMONEY,
        RequestProcessor\Base::OLAMONEY,
        RequestProcessor\Base::AMAZONPAY,
        RequestProcessor\Base::FREECHARGE,
        RequestProcessor\Base::UPI_AIRTEL,
        RequestProcessor\Base::UPI_AXIS,
        RequestProcessor\Base::UPI_YESBANK,
        RequestProcessor\Base::CARD_FSS_BOB,
        RequestProcessor\Base::NETBANKING_SBI,
        RequestProcessor\Base::NETBANKING_IDFC,
        RequestProcessor\Base::NETBANKING_EQUITAS,
        RequestProcessor\Base::CARDLESS_EMI_FLEXMONEY,
    ];

    const GATEWAY_WITH_COLUMN_REPLACE_CASES = [
        RequestProcessor\Base::PAYZAPP => [
            '%' => '_percentage',
        ],
    ];

    //
    // This is the list of gateways for which file is xlsx but comes with extension xls.
    // Maatwebsite not able to parse this file but spout is able to. Hence irrespective of extension,
    // will use spout library to parse files for these gateways.
    //
    const SPOUT_GATEWAYS = [
        RequestProcessor\Base::VIRTUAL_ACC_YESBANK,
        RequestProcessor\Base::NETBANKING_PNB
    ];

    const MAX_SHEETS_ALLOWED = 3;
    const ROW_CHUNK_SIZE = 3000;

    const THRESHOLD_FOR_COLUMN_HEADER_MISMATCH = 15;
    const THRESHOLD_FOR_METADATA_ROW_COUNT     = 1;

    protected $dataArray;

    protected $gateway;

    public function __construct(string $gateway = null)
    {
        parent::__construct();

        $this->gateway = $gateway;
    }

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
     * Converts excel sheet to in memory array, by using spout or maatwebsite excel parser
     * depending on the extension of the excel file
     *
     * @param array $fileDetails details of the file being processed
     * @param array $sheetNames  sheet names to be considered
     * @param int   $startRow
     * @param array $keyColumnsNames
     * @param string $gateway
     *
     * @return array
     */
    public function convertExcelToArray(array $fileDetails, $sheetNames, int $startRow, $keyColumnsNames = [], string $gateway = null)
    {
        if ($this->shouldUseSpoutLib($fileDetails[FileProcessor::EXTENSION], $gateway) === true)
        {
            // getting contents using spout library for xlsx
            $sheetsContents = $this->getRowsFromExcelSheetsSpout($fileDetails, $sheetNames, $startRow, $keyColumnsNames);
        }
        else
        {
            $sheetsContents = $this->getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames, $startRow, $keyColumnsNames);
        }

        $fileContents = [];

        foreach ($sheetsContents as $sheetName => $rows)
        {
            if (empty($rows) === true)
            {
                // This would happen when the sheet name sent, does not exist
                continue;
            }

            $fileContents[$sheetName] = $rows;
        }

        return $fileContents;
    }

    public function getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames = [], $startRow = 1, $keyColumnNames)
    {
        $timeStarted = microtime(true);

        $this->trace->debug(
            TraceCode::RECON_INFO, [
                'info_code' => 'GET_EXCEL_ROWS_BEGIN',
                'file_details' => $fileDetails,
                'gateway' => get_called_class(),
        ]);

        if (empty($sheetNames) === false)
        {
            $allSheetsContent = $this->getRowsFromExcelSheetsOptimizedWithSheetNames(
                                                            $fileDetails, $sheetNames, $keyColumnNames, $startRow);
        }
        else
        {
            $allSheetsContent = $this->getRowsFromExcelSheetsOptimizedWithSheetIndices($fileDetails, $keyColumnNames, $startRow);
        }

        $this->trace->debug(
            TraceCode::RECON_INFO,
                [
                    'info_code'         => 'GET_EXCEL_ROWS_END',
                    'file_details'      => $fileDetails,
                    'gateway'           => get_called_class(),
                    'time_taken'        => (microtime(true) - $timeStarted)
                ]);

        return $allSheetsContent;
    }

    /**
     * This function uses Spout library to read data from Excel sheets
     *
     * @param $fileDetails
     * @param array $sheetNames
     * @param int $startRow
     * @param array $keyColumnNames
     * @return array excel sheet content of mentioned file
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    public function getRowsFromExcelSheetsSpout($fileDetails, $sheetNames = [], int $startRow = 1, $keyColumnNames)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $reader = ReaderFactory::createFromType(Type::XLSX);
        $reader->setShouldPreserveEmptyRows(false);
        $reader->setShouldFormatDates(true);
        $reader->open($filePath);

        if (empty($sheetNames) === false)
        {
            return $this->getRowsFromExcelSheetsWithSheetNamesSpout($reader, $sheetNames, $startRow, $keyColumnNames);
        }

        return $this->getRowsFromExcelSheetsWithIndicesSpout($reader, $startRow, $keyColumnNames);
    }

    public function convertExcelSheetToArray($sheet)
    {
        $rows = $sheet->toArray();

        return $rows;
    }

    public function convertCsvToArray(
        array $fileDetails,
        array $columnHeaders = [],
        array $linesToSkip = [],
        string $delimiter = ',',
        string $gateway): array
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
                    if ($this->isValidRow($columnHeadersCount, count($row)) === false)
                    {
                        //
                        // This can happen if any row in the file has dummy data.
                        // Not throwing exception so that further rows get processed.
                        //
                        $this->trace->debug(
                            TraceCode::RECON_ALERT,
                            [
                                'info_code'     => InfoCode::COLUMN_HEADER_MISMATCH,
                                'gateway'       => $this->gateway,
                                'header_count'  => $columnHeadersCount,
                                'row_count'     => count($row),
                                'file_details'  => [
                                    'column_headers' => $columnHeaders,
                                    'row'            => $row
                                ],
                            ]);

                        continue;
                    }

                    // Enabling header normalization for limited gateways for now.
                    // Will migrate other gateways gradually.
                    if (in_array($gateway,self::NORMALIZED_HEADER_GATEWAYS, true) === true)
                    {
                        //Normalizes the header values of file
                        $this->modifyHeaderBeforeNormalization($columnHeaders);

                        $columnHeaders = $this->normalizeHeaders($columnHeaders);
                    }

                    // Combines the columnHeaders(keys) with the row(values)
                    // We pad which ever among header or row column count is
                    // lower and then do array combine
                    $data[] = array_combine_pad($columnHeaders, $row);
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
     * @param $keyColumnNames
     * @param $startRow
     * @return array
     */
    protected function getRowsFromExcelSheetsOptimizedWithSheetIndices(array $fileDetails, $keyColumnNames, $startRow)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        // Initializing sheet content variables
        $sheetContent = [
            'all_sheets_content'     => [],
            'column_headers'         => [],
            'column_headers_count'   => null,
            'key_columns'            => $keyColumnNames
        ];

        foreach (range(0, self::MAX_SHEETS_ALLOWED) as $index)
        {
            $randomSheetName = 'sheet' . $index;
            $sheetContent['all_sheets_content'][$randomSheetName] = [];

            $timeStarted = microtime(true);

            $this->trace->debug(
                TraceCode::RECON_INFO,
                    [
                        'info_code'     => 'PROCESS_EXCEL_SHEET_BEGIN',
                        'file_details'  => $fileDetails,
                        'sheet_details' => $randomSheetName,
                        'gateway'       => get_called_class()
                    ]);

            // In excel 2.1, startRow is actually the heading Row. To maintain the same behaviour
            // In excel 3.1, we will set startRow as $startRow + 1 and heading Row  s $startRow
            $import = new ChunkImport($startRow);

            if (empty($keyColumnNames) === false)
            {
                $import = new ReconKeyColumnChunkImport($startRow);
            }

            $import->setSheets($index)
                ->setChunk(self::ROW_CHUNK_SIZE, function($results) use ($randomSheetName, & $sheetContent, $keyColumnNames) {
                    if (empty($keyColumnNames) === false)
                    {
                        $this->setExcelSheetContentWithKeyColumnNames($results, $randomSheetName, $sheetContent);
                    }
                    else
                    {
                        $this->setExcelSheetContent($results, $randomSheetName, $sheetContent);
                    }
                })
                ->import($filePath);

            $this->trace->debug(
                TraceCode::RECON_INFO,
                    [
                        'info_code'         => 'PROCESS_EXCEL_SHEET_END',
                        'file_details'      => $fileDetails,
                        'sheet_details'     => $randomSheetName,
                        'gateway'           => get_called_class(),
                        'time_taken'        => (microtime(true) - $timeStarted)
                    ]);
        }

        return $sheetContent['all_sheets_content'];
    }

    protected function getRowsFromExcelSheetsOptimizedWithSheetNames(
        array $fileDetails,
        array $sheetNames,
        array $keyColumnNames,
        int $startRow = 1)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        // Initializing sheet content variables
        $sheetContent = [
            'all_sheets_content'     => [],
            'column_headers'         => [],
            'column_headers_count'   => null,
            'key_columns'            => $keyColumnNames
        ];

        foreach ($sheetNames as $sheetName)
        {
            $sheetContent['all_sheets_content'][$sheetName] = [];

            //
            // for each sheet, we will set the header when we encounter it.
            // so resetting it here, else the previous sheet's header will
            // get applied in the current sheet's rows instead of finding its
            // own header based on key columns
            //
            $sheetContent['column_headers'] = [];

            $timeStarted = microtime(true);

            $this->trace->debug(
            TraceCode::RECON_INFO,
                [
                    'info_code'     => 'PROCESS_EXCEL_SHEET_BEGIN',
                    'file_details'  => $fileDetails,
                    'sheet_details' => $sheetName,
                    'gateway'       => get_called_class()
                ]);

            try
            {
                $import = new ChunkImport($startRow);

                if (empty($keyColumnNames) === false)
                {
                    $import = new ReconKeyColumnChunkImport($startRow);
                }

                $import
                    ->setSheets($sheetName)
                    ->setChunk(self::ROW_CHUNK_SIZE, function($results) use ($sheetName, & $sheetContent, $keyColumnNames) {
                       if (empty($keyColumnNames) === false)
                       {
                           $this->setExcelSheetContentWithKeyColumnNames($results, $sheetName, $sheetContent);
                       }
                       else
                       {
                           $this->setExcelSheetContent($results, $sheetName, $sheetContent);
                       }
                   })
                    ->import($filePath);
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

            $this->trace->debug(
                TraceCode::RECON_INFO,
                [
                    'info_code'     => 'PROCESS_EXCEL_SHEET_END',
                    'file_details'  => $fileDetails,
                    'sheet_details' => $sheetName,
                    'gateway'       => get_called_class(),
                    'time_taken'    => (microtime(true) - $timeStarted)
                ]);
        }

        return $sheetContent['all_sheets_content'];
    }

    protected function shouldUseSpoutLib(string $extension, string $gateway = null): bool
    {
        return ($extension === Format::XLSX) or
                (in_array($gateway, self::SPOUT_GATEWAYS, true) === true);
    }

    protected function getRowsFromExcelSheetsWithIndicesSpout($reader, int $startRow = 1, $keyColumnNames)
    {
        $allSheetsContent = [];

        foreach ($reader->getSheetIterator() as $sheet)
        {
            $index = $sheet->getIndex();

            $sheetName = 'sheet' . $index;

            if (empty($keyColumnNames) === false)
            {
                $this->setExcelSheetContentForSpoutWithKeyColumns($allSheetsContent, $sheet, $sheetName, $startRow, $keyColumnNames);
            }
            else
            {
                $this->setExcelSheetContentForSpout($allSheetsContent, $sheet, $sheetName, $startRow);
            }
        }

        return $allSheetsContent;
    }

    protected function getRowsFromExcelSheetsWithSheetNamesSpout(
        $reader,
        array $sheetNames,
        int $startRow = 1,
        $keyColumnNames)
    {
        $allSheetsContent = [];

        foreach ($reader->getSheetIterator() as $sheet)
        {
            $sheetName = $sheet->getName();

            if (in_array($sheetName, $sheetNames, true) === false)
            {
                continue;
            }

            if (empty($keyColumnNames) === false)
            {
                $this->setExcelSheetContentForSpoutWithKeyColumns(
                    $allSheetsContent, $sheet, $sheetName, $startRow, $keyColumnNames);
            }
            else
            {
                $this->setExcelSheetContentForSpout($allSheetsContent, $sheet, $sheetName, $startRow);
            }
        }

        return $allSheetsContent;
    }

    protected function setExcelSheetContentForSpout(array & $allSheetsContent, $sheet, string $sheetName, int $startRow = 1)
    {
        $sheetHeaders = [];

        $allSheetsContent[$sheetName] = [];

        $rowIterator = $sheet->getRowIterator();

        foreach ($rowIterator as $row)
        {
            $row = $row->toArray();

            if ($rowIterator->key() < $startRow)
            {
                continue;
            }

            // this deals with the empty rows
            if (count(array_filter($row)) === 0)
            {
                continue;
            }

            if ($rowIterator->key() === $startRow)
            {
                $this->modifyHeaderBeforeNormalization($row);
                $sheetHeaders = $this->normalizeHeaders($row);
            }
            else
            {
                if ($this->isValidRow(count($sheetHeaders), count($row)) === true)
                {
                    $allSheetsContent[$sheetName][] = array_combine_pad($sheetHeaders, $row);
                }
                // breaking case when count difference is greater than the threshold
                else
                {
                    $this->trace->debug(
                        TraceCode::RECON_ALERT,
                            [
                                'info_code'     => InfoCode::COLUMN_HEADER_MISMATCH,
                                'gateway'       => $this->gateway,
                                'header_count'  => count($sheetHeaders),
                                'row_count'     => count($row),
                                'file_details'  => [
                                    'column_headers' => $sheetHeaders,
                                    'row'            => $row
                                ],
                            ]);

                    continue;
                }
            }
        }
    }

    protected function setExcelSheetContentForSpoutWithKeyColumns(
        array & $allSheetsContent,
        $sheet,
        string $sheetName,
        int $startRow = 1,
        $keyColumnNames)
    {
        $sheetHeaders = [];

        $allSheetsContent[$sheetName] = [];

        $rowIterator = $sheet->getRowIterator();

        foreach ($rowIterator as $row)
        {
            $row = $row->toArray();

            if ($rowIterator->key() < $startRow)
            {
                continue;
            }

            // this deals with the empty rows
            if (count(array_filter($row)) === 0)
            {
                continue;
            }

            // for each row, we check if it is header
            $this->modifyHeaderBeforeNormalization($row);
            $probableSheetHeaders = $this->normalizeHeaders($row);

            $commonColumn = array_intersect($probableSheetHeaders, array_keys($keyColumnNames));

            if (empty($commonColumn) === false)
            {
                $sheetHeaders = $probableSheetHeaders;

                continue;
            }

            if (empty($sheetHeaders) === true)
            {
                // sheet headers not encountered till now and thus not set
                continue;
            }

            if ($this->isValidRow(count($sheetHeaders), count($row)) === true)
            {
                $allSheetsContent[$sheetName][] = array_combine($sheetHeaders, $row);
            }
            // breaking case when count difference is greater than the threshold
            else
            {
                $this->trace->debug(
                    TraceCode::RECON_ALERT,
                    [
                        'info_code'     => InfoCode::COLUMN_HEADER_MISMATCH,
                        'gateway'       => $this->gateway,
                        'header_count'  => count($sheetHeaders),
                        'row_count'     => count($row),
                        'file_details'  => [
                            'column_headers' => $sheetHeaders,
                            'row'            => $row
                        ],
                    ]);

                continue;
            }
        }
    }

    protected function setExcelSheetContent($results, string $sheetName, & $sheetContent)
    {
        foreach ($results as $row)
        {
            // this deals with the empty rows
            if (count(array_filter($row->all())) === 0)
            {
                continue;
            }

            // Currently, since it returns an array of rows, there's no
            // way to get the sheet names. And we cannot let it return
            // an array of sheets because chunk works only on a
            // cell collection (rows) and not on a row collection (sheets)
            $sheetContent['all_sheets_content'][$sheetName][] = $row->all();
        }
    }

    protected function setExcelSheetContentWithKeyColumnNames($results, string $sheetName, array & $sheetContent)
    {
        // We get a collection consisting of collections
        // in results now, so using all() to get it's items.
        foreach ($results->all() as $row)
        {
            // this deals with the empty rows
            if (count(array_filter($row->all())) === 0)
            {
                continue;
            }

            // for each row, check if it is a recon row or header
            if ($this->setColumnHeaderIfApplicable(array_values($row->all()), $sheetContent) === true)
            {
                // Header encountered
                $sheetContent['column_headers_count'] = count($sheetContent['column_headers']);
            }
            else
            {
                // check if the column header is set
                if (empty($sheetContent['column_headers']) === true)
                {
                    continue;
                }

                if ($sheetContent['column_headers_count'] !== count($row->all()))
                {
                    //
                    // This happens sometimes, when column header was set in previous excel chunk,
                    // and this current chunk starts reading next chunk of rows, but the last few
                    // columns are blank in the row.
                    // In this case, chunk reads columns up-to last non null value and thus
                    // count($row) becomes less than that of the column header.
                    // So, here we need to slice the header to the size of the row and then do array_combine()
                    //
                    $sheetContent['all_sheets_content'][$sheetName][] = array_combine_slice($sheetContent['column_headers'], $row->all());

                    continue;
                }

                $sheetContent['all_sheets_content'][$sheetName][] = array_combine($sheetContent['column_headers'], $row->all());
            }
        }
    }

    /**
     * For each row, we check if it is a header, if it is header
     * then update the columnHeaders and name
     *
     * @param $row
     * @param $sheetContent
     * @return bool : returns true if we encounter a header and thus
     * columnHeaders and sheetName are changed
     */
    protected function setColumnHeaderIfApplicable($row, & $sheetContent)
    {
        $this->modifyHeaderBeforeNormalization($row);
        $tempHeader = $this->normalizeHeaders($row);

        $keyColumnNames = $sheetContent['key_columns'];

        $commonColumn = array_intersect($tempHeader, array_keys($keyColumnNames));

        if (empty($commonColumn) === false)
        {
            $sheetContent['column_headers'] = $tempHeader;

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Returns true if this is a valid row.
     * If the header column and row count mismatch is beyond the
     * threshold defined, we consider it as invalid row (i.e. metadata)
     *
     * If the diff in count <= threshold, but the row count is 1 then
     * also we return false, as it is a metadata row.
     * @param $headerCount
     * @param $rowCount
     * @return bool
     */
    protected function isValidRow($headerCount, $rowCount)
    {
        if ((abs($headerCount - $rowCount) > self::THRESHOLD_FOR_COLUMN_HEADER_MISMATCH) or
            ($rowCount <= self::THRESHOLD_FOR_METADATA_ROW_COUNT))
        {
            return false;
        }

        return true;
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
     * For some gateways, we are getting special chars in file
     * and thus during normalization of headers two column of
     * MIS file getting mapped to same string.
     * i.e For PayZapp, IGST and IGST% both get modified to igst,
     * and thus data getting lost.
     * Here we modify IGST% to IGST_percentage before sending the
     * header for normalization, to avoid above issue.
     * @param array $columnHeader
     */
    protected function modifyHeaderBeforeNormalization(array &$columnHeader)
    {
        if (in_array($this->gateway, array_keys(self::GATEWAY_WITH_COLUMN_REPLACE_CASES), true) === false)
        {
            return;
        }

        $specialChars = self::GATEWAY_WITH_COLUMN_REPLACE_CASES[$this->gateway];

        foreach ($columnHeader as &$col)
        {
            foreach ($specialChars as $char => $replaceString)
            {
                $col = str_replace($char, $replaceString, $col);
            }
        }
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
    public function normalizeHeaders(array $headers)
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
            $val = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $val); // nosemgrep :php.lang.security.preg-replace-eval.preg-replace-eval

            // Remove all characters that are not the separator,
            // letters, numbers, or whitespace.
            $val = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', mb_strtolower($val)); // nosemgrep :php.lang.security.preg-replace-eval.preg-replace-eval

            // Replace all separator characters and whitespace by a single separator
            $val = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $val); // nosemgrep :php.lang.security.preg-replace-eval.preg-replace-eval
            $val = trim($val, $separator);

            array_push($normalized, $val);
        }

        return $normalized;
    }
}
