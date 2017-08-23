<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\FileProcessor;

class Orchestrator extends Base\Core
{
    const ATTACHMENT_COUNT = 'attachment_count';
    const MERCHANT         = 'merchant';
    const TYPE             = 'type';
    const EXTRA_INPUT_TYPE = 'extra_input_type';

    /******************
     * Merchant constants
     ******************/

    const IRCTC                = 'irctc';

    /*********************
     * Instance variables
     *********************/

    protected $allFilesContents;
    protected $allFilesDetails;

    /********************
     * Instance objects
     ********************/

    protected $validator;
    protected $fileProcessor;
    protected $baseFileProcessor;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new Validator;

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
        $this->validator->validateMerchant($input);

        $this->validator->validateAttachments($input);

        $this->setMerchantProcessor($input['merchant']);

        $allFilesDetails = $this->getFileDetailsFromInput($input);

        $this->validator->validateFileDetails($allFilesDetails, $input['merchant']);

        return $this->orchestrate($allFilesDetails);
    }


    /**
     * Validates each file.
     * Gets the content of each file and stores it in an array.
     * Deletes the file from local storage.
     * Calls the Merchant File Processor with
     * all the file details and file contents.
     *
     * @throws Exception\BadRequestException
     */
    protected function orchestrate(array $fileDetails)
    {
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
                $this->getFileContents($fileDetail);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::MERCHANT_FILE_SKIP,
                    ['file_detail' => $fileDetail]
                );

                // Don't get the content of the file.
                continue;
            }

            //
            // Delete the file. We have all the data in $allFilesContents.
            //
            $this->baseFileProcessor->deleteFileLocally($fileDetail[FileProcessor::FILE_PATH]);
        }

        if (empty($this->allFilesContents) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File contents are empty.',
                [
                    'all_files_details' => $this->allFilesDetails,
                ]);
        }

        return $this->fileProcessor->process($this->allFilesContents);
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

        $this->allFilesContents[$type] = $csvArray;
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
