<?php

namespace RZP\Reconciliator;

use DirectoryIterator;

use App;
use RZP\Trace\TraceCode;
use RZP\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Orchestrator
{
    const GATEWAY = 'gateway';

    /**
     * This contains file details, sheet details and email details,
     * whenever applicable. It does not contain the actual content.
     * It's all meta data.
     */
    const EXTRA_DETAILS = 'extra_details';
    const EMAIL_DETAILS = 'email_details';
    const ATTACHMENT_COUNT = 'attachment_count';

    /******************
     * Bank constants
     ******************/

    const HDFC  = 'HDFC';
    const AXIS  = 'Axis';
    const KOTAK = 'Kotak';
    const BILLDESK = 'BillDesk';
    const PAYZAPP = 'PayZapp';
    const MOBIKWIK = 'Mobikwik';
    const PAYTM = 'Paytm';
    const ADMIN = 'admin';

    /**
     * The gateway names should be the same name as the directories present under 'reconciliator'
     */
    const GATEWAY_SENDER_MAPPING = [
        self::HDFC => ['prashanth@razorpay.com'],
        self::AXIS => ['prashanth@razorpay.com'],
        self::BILLDESK => ['prashanth@razorpay.com'],
        self::PAYZAPP  => ['prashanth@razorpay.com'],
        self::MOBIKWIK => ['prashanth@razorpay.com'],
        self::PAYTM    => ['prashanth@razorpay.com'],
        // Used when someone from the team needs to send the
        // reconciliation file via mail for reconciliation.
        self::ADMIN => ['prashanth.yv@razorpay.com'],
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

    public function __construct()
    {
        $this->increaseAllowedSystemLimits();

        $this->app = App::getFacadeRoot();

        $this->messenger = new Messenger();
        $this->validator = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->converter = new Converter;
    }

    /**
     * Determines whether the reconciliation request is manual or
     * via MailGun and gets the files details accordingly.
     *
     * @param array $input The input received from the route.
     * @return int Status code. Currently, always returns a 200.
     *             Will raise alerts in case of issues.
     * @throws Exception\ReconciliationException Raised when there are no
     *                                           files to reconcile.
     */
    public function initiateReconciliationProcess(array $input)
    {
        $this->app['trace']->info(
            TraceCode::RECON_REQUEST,
            $input
        );

        // Checks if it's manual call or mailgun call
        if ((isset($input['manual']) === true) and ($input['manual'] === "1"))
        {
            // Sets the gateway reconciliator object and
            // Gets all the file details from the input.
            $this->allFilesDetails = $this->manualEntry($input);
        }
        else
        {
            // Sets the gateway reconciliator object and
            // Gets all the file details from the input.
            $this->allFilesDetails = $this->mailGunEntry($input);
        }

        // There must be at least one file. Otherwise, error.
        if (empty($this->allFilesDetails) === true)
        {
            throw new Exception\ReconciliationException(
                'File details are empty.'
            );
        }

        $this->app['trace']->info(
            TraceCode::RECON_FILE_DETAILS,
            $this->allFilesDetails
        );

        return $this->orchestrate();
    }

    /**
     * Validations and getting file details are handled by this function
     * when reconciliation route is hit via REST Client/dashboard.
     *
     * @param array $input The input received from the route.
     * @return array Details of all the files received from the input.
     */
    protected function manualEntry(array $input)
    {
        // Validates the input received.
        // All the attachment files names should start with 'attachment-'
        // Also, adds attachment-count to input, if not present already.
        $this->validator->validateAttachments($input);

        $inputDetails = $this->getManualInputDetails($input);

        // Figures out the gateway and
        // sets the gateway reconciliator object for the orchestrator
        $this->setGatewayForManual($inputDetails);

        $allFilesDetails = $this->getFileDetailsFromInput($inputDetails, $input);

        return $allFilesDetails;
    }

    /**
     * Getting all files details is handled by this function when the
     * reconciliation route is hit by MailGun.
     *
     * @param array $input The input received from the route.
     * @return array Details of all the files received from the input.
     */
    protected function mailGunEntry(array $input)
    {
        // Gets the email details and validates the email details.
        $this->emailDetails = $this->getEmailDetails($input);
        $this->validator->filterEmails($this->emailDetails);

        // Figures out the gateway and sets the gateway reconciliator object for
        // the orchestrator, using the input details.
        $this->setGatewayFromEmailId();

        $allFilesDetails = $this->getFileDetailsFromInput($this->emailDetails, $input);

        return $allFilesDetails;
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
            $this->app['trace']->info(
                TraceCode::RECON_FILE_DETAILS,
                [
                    'message' => 'File details of the file being orchestrated.',
                    'file_details' => $fileDetails
                ]
            );

            $skipFile = $this->checkFileSkip($fileDetails);

            if ($skipFile === true)
            {
                $this->handleFileSkip($file, $fileDetails);
                continue;
            }

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
                        'message'      => 'Skipping file because not able to convert file content to array. -> ' . $ex->getMessage(),
                        'file_details' => $fileDetails,
                        //'gateway'      => get_class($this->gatewayReconciliator),
                        'gateway'      => (new \ReflectionClass($this->gatewayReconciliator))->getNamespaceName()
                    ]);

                $this->app['trace']->traceException($ex);

                $this->handleFileSkip($file, $fileDetails);

                // Don't get the content of the file.
                continue;
            }

            // Delete the file. We have all the data in $allFilesContents.
            $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);
        }

        if (empty($this->allFilesContents) === true)
        {
            throw new Exception\ReconciliationException(
                'File contents are empty.',
                [
                    'all_files_details' => $this->allFilesDetails,
                ]
            );
        }

        return $this->gatewayReconciliator->startReconciliation($this->allFilesContents);
    }

    protected function checkFileSkip($fileDetails)
    {
        // Checks if this particular file needs to be excluded for the gateway
        $inExclude = $this->gatewayReconciliator->inExcludeList($fileDetails);

        if ($inExclude === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_FILE_SKIP,
                [
                    'trace_code'   => TraceCode::RECON_FILE_SKIP,
                    'message'      => 'Skipping file because it is present in the exclude list of the gateway.',
                    'file_details' => $fileDetails,
                    //'gateway'      => get_class($this->gatewayReconciliator),
                    'gateway'      => (new \ReflectionClass($this->gatewayReconciliator))->getNamespaceName()
                ]);

            return true;
        }

        // Validates the file type, size, etc..
        $validate = $this->validator->validateFile($fileDetails);

        if ($validate === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'   => TraceCode::RECON_FILE_SKIP,
                    'message'      => 'Skipping file because validations failed.',
                    'file_details' => $fileDetails,
                    //'gateway'      => get_class($this->gatewayReconciliator),
                    'gateway'      => (new \ReflectionClass($this->gatewayReconciliator))->getNamespaceName()
                ]);

            return true;
        }

        return false;
    }

    protected function handleFileSkip($file, array $fileDetails)
    {
        $this->fileProcessor->deleteFileLocally($fileDetails[FileProcessor::FILE_PATH]);

        // Remove the file from allFiles variable, since this file is now, not part of reconciliation.
        unset($this->allFilesDetails[$file]);
    }

    /**
     * Gets the required details from the input, structured.
     * This includes the gateway for which the reconciliation
     * needs to be done and the number of attachments. This is an
     * optional parameter.
     *
     * @param array $input
     * @return array Structured input details
     */
    protected function getManualInputDetails(array $input)
    {
        $inputDetails = [
            self::ATTACHMENT_COUNT => $input['attachment-count'],
            self::GATEWAY => $input['gateway'],
        ];

        return $inputDetails;
    }

    protected function getEmailDetails($input)
    {
        $emailDetails = [
            'from' => $input['sender'],
            'subject' => $input['subject'],
            'to' => $input['recipient'],
            'timestamp' => $input['timestamp'],
            'body' => $input['stripped-text'],
        ];

        // Validates that attachments are present in the email.
        $this->validator->validateAttachments($input);

        $emailDetails[self::ATTACHMENT_COUNT] = $input['attachment-count'];

        return $emailDetails;
    }

    /**
     * Uses the gateway input sent in the route, to set the gateway
     * reconciliator object for the class. The gateway should be
     * present in the GATEWAY_SENDER_MAPPING list.
     *
     * @param array $inputDetails
     * @throws Exception\ReconciliationException
     */
    protected function setGatewayForManual(array $inputDetails)
    {
        // In manual, the input params should contain what gateway is it.
        $gateway = $inputDetails[self::GATEWAY];

        // This is a validation for the value of the gateway input received.
        if (array_key_exists($gateway, self::GATEWAY_SENDER_MAPPING) === false)
        {
            throw new Exception\ReconciliationException(
                'Invalid gateway param. Not in the allowed list of gateway params.',
                ['gateway' => $gateway]
            );
        }

        // Sets the gateway reconciliator object for the orchestrator.
        $this->setGatewayReconciliatorObject($gateway);
    }

    /**
     * Uses the 'from' email ID to figure out the gateway.
     * If 'from' email ID is of one of the whitelisted admins,
     * it uses the 'subject' to figure out the gateway.
     * It also sets the gateway reconciliator object for the class.
     *
     * @throws Exception\ReconciliationException
     */
    protected function setGatewayFromEmailId()
    {
        $fromEmailId = $this->emailDetails['from'];

        // For a particular gateway, reconciliation files can be sent from more than one email ID.
        $gateway = $this->getKeyFromSubArrayMatch($fromEmailId, self::GATEWAY_SENDER_MAPPING);

        if (empty($gateway) === true)
        {
            throw new Exception\ReconciliationException(
                'Email ID not present in Sender-Gateway mapping.',
                ['email_id' => $fromEmailId]
            );
        }

        if ($gateway === self::ADMIN)
        {
            $gateway = $this->emailDetails['subject'];
            assert(in_array($gateway, array_keys(self::GATEWAY_SENDER_MAPPING)),
                    "[Admin] Invalid/Unrecognized gateway sent in the subject line.");
        }

        $this->setGatewayReconciliatorObject($gateway);
    }

    protected function getFileDetailsFromInput($inputDetails, $input)
    {
        $allFilesDetails = [];

        // Goes through each file and gets the file details.
        foreach (range(1, $inputDetails[self::ATTACHMENT_COUNT]) as $attachmentNumber)
        {
            // All the attachment files have to be named as 'attachment-{number}'
            // Validations should take care of this.
            $file = $input['attachment-'.$attachmentNumber];

            // This step is mainly to figure out whether the file is of zip type,
            // since we need to execute a different set of flow ONLY for zip files.
            $fileType = $this->fileProcessor->getTypeOfFile($file);

            // If it's a zip file, get all the details of all the files present in it.
            // Else, get the file details of the attachment.
            if (in_array($fileType, Validator::SUPPORTED_ZIP_EXTENSIONS))
            {
                try
                {
                    // Gets the actual zip file's details first.
                    $zipFileDetails = $this->fileProcessor->getFileDetails($file, FileProcessor::UPLOADED);

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
                    $this->app['trace']->traceException($ex);

                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code'   => TraceCode::RECON_FILE_SKIP,
                            'message'      => 'Skipping file because unzip file caused an exception -> ' . $ex->getMessage(),
                            'file_details' => !empty($extractedFileDetails) ?  $extractedFileDetails : null,
                            'gateway'      => get_class($this->gatewayReconciliator),
                        ]);

                    continue;
                }
            }
            else
            {
                // Except zip, all other file types will return with a single element
                // and not an array. Hence using push here instead of merge.
                $allFilesDetails[] = $this->fileProcessor->getFileDetails($file, FileProcessor::UPLOADED);
            }
        }

        return $allFilesDetails;
    }

    protected function getFileDetailsFromAllZipFiles($zipFilesDetails)
    {
        $allExtractedFileDetails = [];

        foreach ($zipFilesDetails as $zipFileDetails)
        {
            $extractedFileDetails = $this->getFileDetailsFromZipFile($zipFileDetails);
            $allExtractedFileDetails = array_merge($allExtractedFileDetails, $extractedFileDetails);
        }

        return $allExtractedFileDetails;
    }

    /**
     * Returns true only if all the files are zip files.
     * Returns false otherwise.
     *
     * @param $extractedFileDetails
     * @return true if all the files are zip files
     *         false, otherwise.
     */
    protected function isTwoLevelZip($extractedFileDetails)
    {
        foreach ($extractedFileDetails as $efd)
        {
            if ($efd[FileProcessor::EXTENSION] !== FileProcessor::ZIP_EXTENSION)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts the data in file (excel/csv) and sets to in-memory array.
     *
     * @param $fileDetails
     * @throws Exception\ReconciliationException
     */
    protected function getFileContentInArrayAndSet($fileDetails)
    {
        // All file types are segregated into either CSV or Excel.
        $fileType = self::getKeyFromSubArrayMatch(
            $fileDetails[FileProcessor::EXTENSION], FileProcessor::FILE_TYPES_MAPPINGS);

        $fileDetails[FileProcessor::FILE_TYPE] = $fileType;

        if (empty($fileType) === true)
        {
            // Throwing exception instead of raising alert, because the parent function needs to
            // perform some operations if this condition block executes to true.
            throw new Exception\ReconciliationException(
                'Unsupported file type.', ['file_details' => $fileDetails, 'file_type' => $fileType]
            );
        }

        if ($fileType === FileProcessor::EXCEL)
        {
            $this->handleSettingExcelContent($fileDetails);
        }
        else if ($fileType === FileProcessor::CSV)
        {
            $this->handleSettingCsvContent($fileDetails);
        }
        else
        {
            throw new Exception\ReconciliationException(
                'File is neither an Excel nor a CSV type.',
                ['file_details' => $fileDetails]
            );
        }
    }

    protected function setGatewayReconciliatorObject($gateway)
    {
        $gatewayReconciliatorClassName = 'RZP\\Reconciliator' . '\\' . $gateway . '\\' . 'Reconciliate';
        $this->gatewayReconciliator = new $gatewayReconciliatorClassName;
    }

    /**
     * Converts and sets the excel content in an array.
     *
     * @param array $fileDetails
     */
    protected function handleSettingExcelContent(array $fileDetails)
    {
        //
        // Gets the sheet names which need to be collected for the given gateway.
        // Returns empty if there is no restriction on which sheets to collect.
        // If sheetNames returned is empty, ensure that the gateway does not perform
        // any operation based on the sheet name.
        //
        $sheetNames = $this->gatewayReconciliator->getSheetNames();

        $sheetsContents = $this->converter->getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames);

        foreach ($sheetsContents as $sheetName => $rows)
        {
            if (empty($rows) === true)
            {
                // This would happen when the sheet name sent, does not exist
                continue;
            }

            $sheetArray = [];

            foreach ($rows as $cellCollection)
            {
                $sheetArray[] = $cellCollection->all();
            }

            $fileDetails[FileProcessor::SHEET_NAME] = $sheetName;

            $this->setExtraDetails($sheetArray, $fileDetails);

            $this->allFilesContents[] = $sheetArray;
        }

    }

    /**
     * PHPExcel returns back an associative array in case there is
     * only one row and returns back an array of arrays(rows) if there
     * are multiple rows.
     * This functions helps in maintaining consistency across sheets.
     *
     * @param $sheetArray
     */
    protected function handleOneRowSheet(array & $sheetArray)
    {
        //
        // Checks whether the first element is an array in itself
        // If it's not, it means that it's an associative array
        //
        if (is_array(reset($sheetArray)) === false)
        {
            $sheetArray = array($sheetArray);
        }
    }

    protected function handleSettingCsvContent($fileDetails)
    {
        $csvArray = $this->converter->convertCsvToArray($fileDetails);

        $this->setExtraDetails($csvArray, $fileDetails);

        $this->allFilesContents[] = $csvArray;
    }

    protected function setExtraDetails(& $arrayContent, $fileDetails)
    {
        $arrayContent[self::EXTRA_DETAILS][FileProcessor::FILE_DETAILS] = $fileDetails;
        $arrayContent[self::EXTRA_DETAILS][self::EMAIL_DETAILS] = $this->emailDetails;
    }

    /**
     * Unzips the zip file. Iterates through each extracted file and collects
     * the file details.
     *
     * @param array $zipFileDetails Zip file that needs to be extracted.
     * @return array File details of all the files present in the zip file.
     * @throws Exception\ReconciliationException
     */
    protected function getFileDetailsFromZipFile($zipFileDetails)
    {
        $allExtractedFilesDetails = [];

        $zipPassword = $this->gatewayReconciliator->getReconPassword($zipFileDetails);

        // unzipFile unzips the file and stores it in a location.
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zipFileDetails, $zipPassword);

        $unzippedFiles = new DirectoryIterator($unzippedFolderPath);

        // Iterates through each zip file and gets the file details for them.
        foreach ($unzippedFiles as $unzippedFile)
        {
            if ($unzippedFile->isFile() === true)
            {
                $allExtractedFilesDetails[] = $this->fileProcessor
                    ->getFileDetails($unzippedFile, FileProcessor::STORAGE);
            }
        }

        return $allExtractedFilesDetails;
    }

    /**
     * @param $needle
     * @param array $haystack An associative array with array values.
     *                        ['a' => ['b', 'c'], 'd' => ['e', 'f']]
     * @return int|string|null
     */
    public static function getKeyFromSubArrayMatch($needle, array $haystack)
    {
        foreach ($haystack as $key => $subArray)
        {
            if (in_array($needle, $subArray) === true)
            {
                return $key;
            }
        }

        return null;
    }

    /**
     * The reconciliation can run for a long time.
     * Hence, changing the system's execution time limit to 1 hour.
     */
    protected function increaseAllowedSystemLimits()
    {
        set_time_limit(3600);
    }
}