<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\FileProcessor;

class Orchestrator extends Base\Core
{
    const MERCHANT = 'merchant';
    const TYPE     = 'type';
    const IRCTC    = 'irctc';

    protected $fileProcessor;
    protected $baseFileProcessor;

    public function __construct()
    {
        parent::__construct();

        $this->baseFileProcessor = new FileProcessor;
    }

    public function initiateFileProcessing(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_FILE_REQUEST, $input);

        $summary = [];

        try
        {
            $summary = $this->processRequest($input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }

        return $summary;
    }

    /**
     * Identify Processor and processes request
     *
     * @param array $input The input received from the route.
     * @return array Summary of process
     * @throws Exception\BadRequestException
     *
     */
    protected function processRequest(array $input)
    {
        $validator = new Validator;

        $validator->validateMerchant($input);

        $validator->validateAttachments($input);

        $this->setMerchantProcessor($input[self::MERCHANT]);

        $allFilesDetails = $this->getFileDetailsFromInput($input);

        $validator->validateFileDetails($allFilesDetails, $input[self::MERCHANT]);

        return $this->orchestrate($allFilesDetails);
    }

    protected function orchestrate(array $fileDetails)
    {
        $allFilesContents = [];

        // Run validations and conversions on each file
        foreach ($fileDetails as $fileDetail)
        {
            $this->trace->info(
                TraceCode::MERCHANT_FILE_DETAILS,
                [
                    'message'      => 'File details of the file being orchestrated.',
                    'file_detail' => $fileDetail[FileProcessor::FILE_NAME]
                ]
            );

            try
            {
                // Converts to in-memory array and stores it in instance variable..
                list($type, $contents) = $this->getFileContents($fileDetail);

                $allFilesContents[$type] = $contents;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::MERCHANT_FILE_SKIP,
                    ['file_detail' => $fileDetail]
                );
            }

            // Delete the file. We have all the data in $allFilesContents.
            $this->baseFileProcessor->deleteFileLocally($fileDetail[FileProcessor::FILE_PATH]);
        }

        if (empty($allFilesContents) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File contents are empty.',
                [ 'file_details' => $fileDetails]);
        }

        return $this->fileProcessor->process($allFilesContents);
    }

    protected function getFileDetailsFromInput(array $input): array
    {
        $allFilesDetails = [];

        unset($input['merchant']);

        foreach ($input as $file)
        {
            $allFilesDetails[] = $this->baseFileProcessor->getFileDetails($file, FileProcessor::UPLOADED);
        }

        return $allFilesDetails;
    }

    protected function getFileContents($fileDetail)
    {
        $type = $this->getType($fileDetail);

        $headers = $this->getColumnHeaders($type);

        $delimiter = $this->fileProcessor->getDelimiter();

        $csvArray = $this->convertCsvToArray($fileDetail, $headers, $delimiter);

        return [$type, $csvArray];
    }

    protected function getColumnHeaders(string $type)
    {
        $columnHeaders = $this->fileProcessor->getColumnHeaders($type);

        return $columnHeaders;
    }

    protected function getType($fileDetail)
    {
        $fileName = $fileDetail[FileProcessor::FILE_NAME];

        $type = $this->fileProcessor->getType($fileName);

        return $type;
    }

    protected function convertCsvToArray($fileDetails, $columnHeaders = [], $delimiter = ',')
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $data = [];

        $columnHeadersCount = count($columnHeaders);

        $handle = fopen($filePath, 'r');

        if ($handle === false)
        {
            throw new Exception\RuntimeException('Unable to open file . ' . $filePath);
        }

        try
        {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
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
                        throw new Exception\BadRequestValidationFailureException(
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

    protected function setMerchantProcessor(string $merchant)
    {
        $merchantFileProcessorClassName = 'RZP\\Models\\Merchant\\FileProcessor'
                                            . '\\' . studly_case($merchant)
                                            . '\\FileProcessor';

        $this->fileProcessor = new $merchantFileProcessorClassName();
    }
}
