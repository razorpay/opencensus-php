<?php

namespace RZP\Models\Merchant\Orchestrator;

use DirectoryIterator;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\FileStore\Format;

class Orchestrator extends Base\Core
{
    const GATEWAY = 'gateway';

    /**
     * This contains file details, sheet details and email details,
     * whenever applicable. It does not contain the actual content.
     * It's all meta data.
     */
    const EXTRA_DETAILS    = 'extra_details';
    const ATTACHMENT_COUNT = 'attachment_count';

    /**************************
     * Email details constants
     **************************/
    const EMAIL_DETAILS    = 'email_details';
    const FROM             = 'from';
    const TO               = 'to';
    const SUBJECT          = 'subject';
    const TIMESTAMP        = 'timestamp';
    const BODY             = 'body';
    const BODY_HTML_TEXT   = 'body_html_text';

    /******************
     * Merchant constants
     ******************/

    const IRCTC                = 'Irctc';

    /**
     * Gateways for which we run validations on email content
     */
    const EMAIL_VALIDATION = [
        self::IRCTC,
    ];

    /*********************
     * Instance variables
     *********************/

    protected $allFilesContents;
    protected $allFilesDetails;
    protected $emailDetails;

    /********************
     * Instance objects
     ********************/

    protected $validator;
    protected $fileProcessor;
    protected $converter;
    protected $gatewayReconciliator;
    protected $app;
    protected $messenger;
    protected $gateway;

    public function __construct(string $merchant, string $type)
    {
        parent::__construct();

        $fileProcessor = __NAMESPACE__ . '\\' . studly_case($merchant) . '\\' . studly_case($type);

        $this->fileProcessor = new  $fileProcessor();
        $this->validator     = new Validator;
    }

    /**
     * Determines whether the request is manual or via Mailgun, and
     * either throws or suppresses the exception accordingly.
     * Exception is suppressed in the latter case, as we do not want
     * Mailgun to attempt retrying the same request.
     *
     * @param array $input The input received from the route
     *
     * @return array Summary of reconciliation
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
     * Determines whether the reconciliation request is manual or
     * via MailGun and gets the files details accordingly.
     *
     * @param array $input The input received from the route.
     * @return array Summary of reconciliation
     * @throws Exception\ReconciliationException Raised when there are no
     *                                           files to reconcile.
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
            //TODO
            //throw exception
        }

        return $this->orchestrate();
    }


    /**
     * Validates each file.
     * Gets the content of each file and stores it in an array.
     * Deletes the file from local storage.
     * Calls the gateway reconciliator with
     * all the file details and file contents.
     *
     * @throws Exception\ReconciliationException
     */
    protected function orchestrate()
    {
        // Run validations and conversions on each file
        foreach ($this->allFilesDetails as $file => $fileDetails)
        {
            $this->trace->info(
                TraceCode::RECON_FILE_DETAILS,
                [
                    'message'      => 'File details of the file being orchestrated.',
                    'file_details' => $fileDetails
                ]
            );

            try
            {
                // Converts to in-memory array and stores it in instance variable.
                // Might have to move this into Gateway implementation since
                // conversion to array might be different for different gateways.
                $this->getFileContentInArrayAndSet($fileDetails);
            }
            catch (\Exception $ex)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'   => TraceCode::RECON_FILE_SKIP,
                        'message'      => 'Skipping file because not able to convert file content to array. -> ' .
                                            $ex->getMessage(),
                        'file_details' => $fileDetails,
                        'gateway'      => $this->gateway,
                    ]);

                $this->trace->traceException($ex);

                $this->handleFileSkip($file, $fileDetails);

                // Don't get the content of the file.
                continue;
            }

            //
            // Delete the file. We have all the data in $allFilesContents.
            // Ensure that you don't delete the directory by mistake.
            // In case of zip files, that's fine. But otherwise, it'll delete
            // off the settlement folder only.
            //
            $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }

        if (empty($this->allFilesContents) === true)
        {
            throw new Exception\ReconciliationException(
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
            self::TYPE             => $input['refund'],
        ];

        return $inputDetails;
    }

    protected function getEmailDetails($input)
    {
        //
        // Sender info is picked from the 'X-Original-Sender' header, instead
        // of 'sender' or 'from' headers.
        //
        // 'sender' will contain "settlement+{hash}@googlegroups.com", as the
        // mail is being forwarded to Mailgun through our settlements group.
        // 'From' may contain values like "HDFC Bank <payoutreport@hdfcbank.com",
        // formatted by the sender's email client.
        // 'X-Original-Sender' always contains just the email address.
        //

        $emailDetails = [
            self::FROM           => $input['X-Original-Sender'] ?? $input['sender'],
            self::SUBJECT        => $input['subject'],
            self::TO             => $input['recipient'],
            self::TIMESTAMP      => $input['timestamp'],
            self::BODY           => $input['stripped-text'],
            self::BODY_HTML_TEXT => html_entity_decode(strip_tags($input['stripped-html'])),
        ];

        //
        // Validates that attachments are present in the email.
        // In some cases (link based banks), attachment count can be 0.
        // We haven't parsed the email for attachments yet at this point.
        // Hence, sending `true` as the second parameter (allowZeroAttachments).
        //
        $this->validator->validateAttachments($input, true);

        $emailDetails[self::ATTACHMENT_COUNT] = $input['attachment-count'];

        return $emailDetails;
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
            $fileType = $this->fileProcessor->getTypeOfFile($file, $fileLocationType);

            // If it's a zip file, get all the details of all the files present in it.
            // Else, get the file details of the attachment.
            if (in_array($fileType, Validator::SUPPORTED_ZIP_EXTENSIONS))
            {
                $zipFileDetails = [];

                try
                {
                    // Gets the actual zip file's details first.
                    $zipFileDetails = $this->fileProcessor->getFileDetails($file, $fileLocationType);

                    // Gets all files details present in the zip file.
                    $extractedFileDetails = $this->getFileDetailsFromZipFile($zipFileDetails);

                    // Throw an error if there's not even one file in the zip. Ideally, shouldn't happen.
                    if (empty($extractedFileDetails) === true)
                    {
                        // Exception instead of alert, to handle zip extraction exceptions also in the
                        // same alert in the catch block. (Cleaner code).
                        throw new Exception\ReconciliationException(
                            'No files present in the zip file attachment.',
                            ['file_name' => $file->getClientOriginalName()]
                        );
                    }

                    // Checks whether all the extracted files are zips too.
                    $multiLevelZip = $this->isTwoLevelZip($extractedFileDetails);

                    if ($multiLevelZip === true)
                    {
                        $extractedFileDetails = $this->getFileDetailsFromAllZipFiles($extractedFileDetails);
                    }

                    // Using array merge since $extractedFileDetails contains an
                    // array of file details of different files in the zip file.
                    $allFilesDetails = array_merge($allFilesDetails, $extractedFileDetails);
                }
                catch (\Exception $ex)
                {
                    $this->trace->traceException($ex);

                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code'   => TraceCode::RECON_FILE_SKIP,
                            'message'      => 'Skipping file because unzip file caused an exception -> ' .
                                                $ex->getMessage(),
                            'file_details' => !empty($extractedFileDetails) ? $extractedFileDetails : null,
                            'gateway'      => $this->gateway,
                        ]);

                    $this->deleteFileLocallyIfPresent($zipFileDetails);

                    continue;
                }
            }
            else
            {
                // Except zip, all other file types will return with a single element
                // and not an array. Hence using push here instead of merge.
                $allFilesDetails[] = $this->fileProcessor->getFileDetails($file, $fileLocationType);
            }
        }

        return $allFilesDetails;
    }

    protected function deleteFileLocallyIfPresent(array $fileDetails)
    {
        if (isset($fileDetails[FileProcessor::FILE_PATH]) === false)
        {
            return;
        }

        $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
    }

    /**
     * Converts the data in file (excel/csv) and sets to in-memory array.
     *
     * @param $fileDetails
     * @throws Exception\ReconciliationException
     */
    protected function getFileContentInArrayAndSet($fileDetails)
    {
        $columnHeaders = $this->fileProcessor->getHeaders();

        $delimiter = $this->fileProcessor->getDelimiter();

        $csvArray = $this->converter->convertCsvToArray($fileDetails, $columnHeaders, $delimiter);

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
                        //TODO throw exception
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

    protected function setMerchantProcessor($inputDetails)
    {
        $merchantFileProcessorClassName = 'RZP\\Models\\Merchant\\FileProcessor' . '\\' . studly_case($inputDetails['merchant']) . '\\' . studly_case($inputDetails['type']);

        $this->fileProcessor = new $merchantFileProcessorClassName;
    }
}
