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

    const IRCTC                = 'Irctc';

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

    public function __construct()
    {
        parent::__construct();

        $this->validator     = new Validator;

        $this->baseFileProcessor = new FileProcessor;
    }

    /**
     * Get and Process File
     *
     * @param array $input The input received from the route
     *
     * @return array Summary of file processing
     * @throws \Throwable
     */
    public function initiateFileProcessing(array $input)
    {
         $this->trace->info(
            TraceCode::MERCHANT_FILE_REQUEST,
            $input);

        try
        {
            $summary = $this->processRequest($input);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return [];
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
        // Validates the input received.
        // All the attachment files names should start with 'attachment-'
        // Also, adds attachment-count to input, if not present already.
        $this->validator->validateAttachments($input);

        $inputDetails = $this->getInputDetails($input);

        $this->setMerchantProcessor($inputDetails);

        $this->allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input);

        if (empty($this->allFilesDetails) === true)
        {
            throw new Exception\BadRequestException(
                'File Details are empty.'
            );
        }

        return $this->orchestrate();
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
    protected function orchestrate()
    {
        // Run validations and conversions on each file
        foreach ($this->allFilesDetails as $file => $fileDetails)
        {
            $this->trace->info(
                TraceCode::MERCHANT_FILE_DETAILS,
                [
                    'message'      => 'File details of the file being orchestrated.',
                    'file_details' => $fileDetails
                ]
            );

            try
            {
                // Converts to in-memory array and stores it in instance variable..
                $this->getFileContentInArrayAndSet($fileDetails);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::MERCHANT_FILE_SKIP,
                    ['file_details' => $fileDetails]
                );

                // Don't get the content of the file.
                continue;
            }

            //
            // Delete the file. We have all the data in $allFilesContents.
            //
            $this->baseFileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }

        if (empty($this->allFilesContents) === true)
        {
            throw new Exception\BadRequestException(
                'File contents are empty.',
                [
                    'all_files_details' => $this->allFilesDetails,
                ]);
        }

        return $this->fileProcessor->process($this->allFilesContents);
    }

    /**
     * Gets the required details from the input, structured.
     *
     * @param array $input
     * @return array Structured input details
     */
    protected function getInputDetails(array $input)
    {
        $inputDetails = [
            self::ATTACHMENT_COUNT => $input['attachment-count'],
            self::MERCHANT         => $input['merchant'],
            self::TYPE             => $input['type'],
        ];

        if (isset($input[self::EXTRA_INPUT_TYPE]) === true)
        {
            $inputDetails[self::EXTRA_INPUT_TYPE] = $input[self::EXTRA_INPUT_TYPE];
        }

        return $inputDetails;
    }

    /**
     * @param        $inputDetails
     * @param        $input
     * @param string $fileLocationType
     *
     * @return array
     */
    protected function getFileDetailsFromInput(
        $inputDetails,
        $input,
        $fileLocationType = FileProcessor::UPLOADED)
    {
        $allFilesDetails = [];

        // Goes through each file and gets the file details.
        foreach (range(1, $inputDetails[self::ATTACHMENT_COUNT]) as $attachmentNumber)
        {
            // All the attachment files have to be named as 'attachment-{number}'
            // Validations should take care of this.
            $file = $input['attachment-' . $attachmentNumber];

            // This step is mainly to figure out whether the file is of zip type,
            // since we need to execute a different set of flow ONLY for zip files.
            $fileType = $this->baseFileProcessor->getTypeOfFile($file, $fileLocationType);

            $allFilesDetails[] = $this->baseFileProcessor->getFileDetails($file, $fileLocationType);
        }

        return $allFilesDetails;
    }

    protected function getFileContentInArrayAndSet($fileDetails)
    {
        $columnHeaders = $this->fileProcessor->getHeaders();

        $delimiter = $this->fileProcessor->getDelimiter();

        $csvArray = $this->convertCsvToArray($fileDetails, $columnHeaders, $delimiter);

        $this->allFilesContents[] = $csvArray;
    }


    public function convertCsvToArray(
        $fileDetails,
        $columnHeaders = [],
        $delimiter = ',')
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $data = [];

        $columnHeadersCount = count($columnHeaders);

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
                        throw new Exception\BadRequestException(
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

    protected function setMerchantProcessor($inputDetails)
    {
        $merchantFileProcessorClassName = 'RZP\\Models\\Merchant\\FileProcessor' . '\\' . studly_case($inputDetails['merchant']) . '\\' . studly_case($inputDetails['type']);

        $this->fileProcessor = new $merchantFileProcessorClassName($inputDetails);
    }
}
