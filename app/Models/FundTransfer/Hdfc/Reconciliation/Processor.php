<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Hdfc\Headings;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Base\Reconciliation\FileProcessor as BaseProcessor;

class Processor extends BaseProcessor
{
    protected static $fileToReadName  = 'Hdfc_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Hdfc_Settlement_Reconciliation';

    protected static $channel = Channel::HDFC;

    protected static $delimiter = ',';

    protected $isFileLevelError = false;

    protected function storeFile($reconcileFile)
    {
        $this->storeReconciledFile($reconcileFile);
    }

    /**
     * Returns class path of row processor based on reverse file.
     * 1. Valid reverse file [isFileLevelError = false]
     *      - In this case file is a valid reverse file and data can be parsed nornally
     * 2. Failed reverse file [isFileLevelError = true]
     *      - In this case file is a failed reverse file and data has to be parsed differenly.
     *
     * Failed file are ~very~ critical level error. Reason for this could be:
     *      - The transfer file format is wrong.
     *      - Some required data is missing.
     *      - Invalid data has been sent to bank.
     *
     * @param $row
     *
     * @return string
     */
    protected function getRowProcessorNamespace($row)
    {
        if ($this->isFileLevelError === false)
        {
            return __NAMESPACE__ . '\\SuccessRowProcessor';
        }
        else
        {
            return __NAMESPACE__ . '\\FailedRowProcessor';
        }
    }

    protected function setDate($data)
    {
        $date = Carbon::createFromFormat('m/d/Y', $data[0][Headings::TRANSACTION_DATE]);

        //update the format so that recon mail is appended to settlement mail
        $this->date = $date->format('d-m-Y');
    }

    public static function getHeadings(): array
    {
        return Headings::getResponseFileHeadings();
    }

    /**
     * Checks the reverse file extension is same as specified by the bank
     *
     * @param string $filePath
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getFileExtensionForParsing(string $filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        //
        // Sample extension format of reverse file is `.R715`.
        // Sample extension format of Failure file is `.F715`.
        // RegEx below matched 1 char followed by 2 or 3 digits
        //
        if (preg_match('/^[rRfF]\d{3}$/', $extension) === 1)
        {
            return $extension;
        }

        // It's the ack file sent from HDFC indicating that file has been picked up for processing.
        if ($extension === FileStore\Format::CLT)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_FILE_ACKNOWLEDGED,
                [
                    'file' => $filePath
                ]);

            return $extension;
        }

        throw new LogicException(
            "Extension not handled: {$extension}",
            null,
            [
                'file_path' => $filePath
            ]);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getIgnoreExtensions(): array
    {
        return [
            'clt'
        ];
    }

    /**
     * Checks reverse file extension to identify which parsing logic to apply.
     * If the file extension is of [Rr]\d{3} then its a valid reverse file
     * If the file extension is of [Ff]\d{3} then its a filed reverse file
     *      - In this case `isFileLevelError` is made true
     *
     * @param string $filePath
     * @param string $delimiter
     * @return array
     * @throws LogicException
     */
    protected function parseTextFile(string $filePath, string $delimiter = ',')
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (preg_match('/^[rR]\d{3}$/', $extension) === 1)
        {
            return parent::parseTextFile($filePath, self::$delimiter);
        }
        else if (preg_match('/^[fF]\d{3}$/', $extension) === 1)
        {
            $this->trace->error(
                TraceCode::SETTLEMENT_FAILED_REVERSE_FILE,
                [
                    'channel' => self::$channel,
                    'file'    => $filePath
                ]);

            $this->isFileLevelError = true;

            return $this->parseFailedFile($filePath, self::$delimiter);
        }

        throw new LogicException(
            "Extension not handled: {$extension}"
            , null
            , [
                'file_path' => $filePath
            ]);
    }

    /**
     * This will parse the failed files given by bank
     *
     * Sample file content:
     * 20180418195250:Error in Validation of file.
     *
     *  [IMA, , 0011605416, 1000.21, Ramesh, , , Ramesh, Ramesh, Ramesh, , , , 9v6ycmolDs3410, 9v6ycmolDs34JS,
     * 9yHwYD9NTkQbJb, , , , , , , 18/04/2018, , HDFC0001755, HDFC, , , M]
     *      error : Invalid IFSC code for IMPS(P2A) at line no3
     *      error : Invalid Transaction Type at line no :3
     *
     * @param string $filePath
     * @param string $delimiter
     * @return array
     */
    protected function parseFailedFile(string $filePath, string $delimiter)
    {
        $row    = [];

        $rows   = [];

        $errors = [];

        $lines = $this->getFileLines($filePath);

        foreach ($lines as $line)
        {
            $line = trim($line);

            //
            // `[` indicates the start of payment file record which was failed.
            // So we parse the payment record to get the FTA ID (CUSTOMER_REFERENCE_NUMBER).
            // Also we use the charecter `[` as an identifier for every unique record in the file
            //
            if ($line[0] === '[')
            {
                //
                // If failed record found and row has the data then associate collected error with the row
                // and add it to the rows list.
                // Reset the row and error to collect data for new record
                //
                $this->finalizeRecord($rows, $row, $errors);

                // For a new record parse the data and set the required fields
                $value = $this->parseText($line, 0, $delimiter);

                $row = [
                    Headings::ERRORS                    => [],
                    Headings::TRANSACTION_DATE          => trim($value[Headings::TRANSACTION_DATE]),
                    Headings::CUSTOMER_REFERENCE_NUMBER => trim($value[Headings::CUSTOMER_REFERENCE_NUMBER]),
                ];
            }
            else if (empty($row) === false)
            {
                //
                // If there exist a $row and current line doesnt start with `[` then current line in a error detail
                // Parse the error and store in in error list.
                //
                $errors[] = $this->getErrorMessage($line);
            }
        }

        // It will account for the last record. Its just that It wont add it to the $rows list.
        $this->finalizeRecord($rows, $row, $errors);

        return $rows;
    }

    /**
     * if row is not empty then add it to list of rows
     *
     * @param array $rows
     * @param array $row
     * @param array $errors
     */
    protected function finalizeRecord(array & $rows, array & $row, array & $errors)
    {
        if (empty($row) === false)
        {
            $row[Headings::ERRORS] = implode(',', $errors);

            $rows[] = $row;
        }

        $row = $errors = [];
    }

    /**
     * Parse the error details for failed reverse file.
     *
     * @param string $message
     *
     * @return string
     */
    protected function getErrorMessage(string $message)
    {
        //
        // Below RegEx will match the error message format sent by the bank and
        // Extract the valid message removing unwanted data
        //
        preg_match('/error\s:\s(.+)\sat\sline\sno:\d/', $message, $match);

        if (isset($match[1]) === true)
        {
           return $match[1];
        }

        return $message;
    }

    /**
     * It will parse the data of type
     *
     * [IMA, , 0011605416, 1000.21, Ramesh, , , Ramesh, Ramesh, Ramesh, , , , 9v6ycmolDs3410, 9v6ycmolDs34JS,
     * 9yHwYD9NTkQbJb, , , , , , , 18/04/2018, , HDFC0001755, HDFC, , , M]
     *
     * to a valid reverse file array
     *
     * @param string $row
     * @param int    $ix
     * @param string $delimiter
     *
     * @return array
     */
    protected function parseText(string $row, int $ix, string $delimiter)
    {
        $headings = Headings::getRequestFileHeadings();

        $headingCount = count($headings);

        // Will remove all additional characters from the string
        $values = explode($delimiter, substr($row, 1, -1));

        $values = array_slice($values, 0,$headingCount);

        return array_combine($headings, $values);
    }
}
