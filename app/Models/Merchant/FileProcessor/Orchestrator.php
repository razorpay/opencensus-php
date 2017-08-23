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

        $this->validator = new Validator;

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
        // Validates the input received.
        // All the attachment files names should start with 'attachment-'
        // Also, adds attachment-count to input, if not present already.
        $this->validator->validateAttachments($input);

        $inputDetails = $this->getInputDetails($input);

        $this->setMerchantProcessor($inputDetails);

        $this->allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input);

        $this->validator->validateFileDetails($input, $this->allFilesDetails);

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
                    'file_details' => $fileDetails['file_name']
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
            throw new Exception\BadRequestValidationFailureException(
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
        ];

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

            $allFilesDetails[] = $this->baseFileProcessor->getFileDetails($file, $fileLocationType);
        }

        return $allFilesDetails;
    }

    protected function getFileContentInArrayAndSet($fileDetails)
    {
        $columnHeaders = $this->getColumnHeaders($fileDetails);

        $delimiter = $this->fileProcessor->getDelimiter();

        $csvArray = $this->convertCsvToArray($fileDetails, $columnHeaders, $delimiter);

        $this->setExtraDetails($csvArray, $fileDetails);

        $this->allFilesContents[] = $csvArray;
    }

    protected function getColumnHeaders($fileDetails)
    {
        $fileName = $fileDetails[FileProcessor::FILE_NAME];

        $processorType = $this->fileProcessor->getType($fileName);

        $columnHeaders = $this->fileProcessor->getColumnHeadersForType($processorType);

        return $columnHeaders;
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

    protected function setMerchantProcessor($inputDetails)
    {
        $merchantFileProcessorClassName = 'RZP\\Models\\Merchant\\FileProcessor'
                                            . '\\' . studly_case($inputDetails['merchant'])
                                            . '\\FileProcessor';

        $this->fileProcessor = new $merchantFileProcessorClassName($inputDetails);
    }

    protected function setExtraDetails(& $arrayContent, $fileDetails)
    {
        $arrayContent['file_details'] = $fileDetails;
    }
}
