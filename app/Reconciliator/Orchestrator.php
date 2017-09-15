<?php

namespace RZP\Reconciliator;

use DirectoryIterator;

use App;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\FileStore\Format;
use Razorpay\Trace\Logger as Trace;

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
     * Bank constants
     ******************/

    const HDFC                = 'HDFC';
    const AXIS                = 'Axis';
    const KOTAK               = 'Kotak';
    const BILLDESK            = 'BillDesk';
    const PAYZAPP             = 'PayZapp';
    const MOBIKWIK            = 'Mobikwik';
    const PAYTM               = 'Paytm';
    const OLAMONEY            = 'Olamoney';
    const FREECHARGE          = 'Freecharge';
    const NETBANKING_AXIS     = 'NetbankingAxis';
    const NETBANKING_ICICI    = 'NetbankingIcici';
    const NETBANKING_FEDERAL  = 'NetbankingFederal';
    const NETBANKING_BOB      = 'NetbankingBob';
    const NETBANKING_RBL      = 'NetbankingRbl';
    const NETBANKING_INDUSIND = 'NetbankingIndusind';
    const NETBANKING_PNB      = 'NetbankingPnb';
    const VIRTUAL_ACC_KOTAK   = 'VirtualAccKotak';
    const JIOMONEY            = 'Jiomoney';
    const EBS                 = 'Ebs';
    const FIRST_DATA          = 'FirstData';
    const ADMIN               = 'admin';

    /**
     * The gateway names should be the same name as the directories present under 'reconciliator'
     * The banks send their MIS files through this sender address.
     * List email addresses in lower case. Addresses are case insensitive, our checks are not.
     */
    const GATEWAY_SENDER_MAPPING = [
        self::HDFC                => ['payoutreport@hdfcbank.com'],
        self::AXIS                => ['pg.estatements@axisbank.com'],
        self::BILLDESK            => [],
        self::PAYZAPP             => [],
        self::MOBIKWIK            => [],
        self::PAYTM               => [],
        self::KOTAK               => ['bankalerts@kotak.com'],
        self::OLAMONEY            => ['olamoney-noreply@olacabs.com'],
        self::FREECHARGE          => ['noreply@freechargemail.in'],
        self::NETBANKING_AXIS     => ['it.rico@axisbank.com'],
        self::NETBANKING_ICICI    => ['ubpshelp@icicibank.com'],
        self::NETBANKING_FEDERAL  => ['fednetrm@federalbank.co.in'],
        self::NETBANKING_RBL      => ['internetbanking@rblbank.com'],
        self::NETBANKING_INDUSIND => [],
        self::NETBANKING_PNB      => [],
        self::NETBANKING_BOB      => [],
        self::JIOMONEY            => [],
        self::EBS                 => [],
        self::FIRST_DATA          => ['customer.care@icici.mailserv.in'],
        self::VIRTUAL_ACC_KOTAK   => ['kmb.reports@kotak.com'],
        // Used when someone from the team needs to send the
        // reconciliation file via mail for reconciliation.
        self::ADMIN               => ['prashanth.yv@razorpay.com'],
    ];

    /**
     * Gateways for which we run validations on email content
     */
    const GATEWAY_EMAIL_VALIDATION = [
        self::HDFC,
        self::AXIS,
        self::KOTAK,
        self::OLAMONEY,
        self::FREECHARGE,
        self::FIRST_DATA,
        self::NETBANKING_AXIS,
        self::NETBANKING_ICICI,
        self::NETBANKING_FEDERAL,
        self::VIRTUAL_ACC_KOTAK,
    ];

    /**
     * Banks or Wallets which do not give the MIS file in attachments but as a
     * link
     */
    const LINK_BASED_BANKS = [
        self::FREECHARGE,
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

    public function __construct()
    {
        parent::__construct();

        $this->increaseAllowedSystemLimits();

        $this->messenger     = new Messenger;
        $this->validator     = new Validator;
        $this->fileProcessor = new FileProcessor;
        $this->converter     = new Converter;
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
    public function initiateReconciliationProcess(array $input)
    {
        $this->traceReconRequest($input);

        try
        {
            $summary = $this->processReconciliationRequest($input);
        }
        catch (\Throwable $e)
        {
            if ($this->isManualRequest($input) === true)
            {
                $this->trace->traceException(
                    $e, Trace::ERROR, TraceCode::RECON_ALERT,
                    (array) json_decode($e->getMessage()));

                throw $e;
            }

            $this->trace->traceException(
                $e, Trace::DEBUG, TraceCode::RECON_ALERT,
                (array) json_decode($e->getMessage()));

            // We do not throw an exception as route is hit via Mailgun,
            // and Mailgun will attempt retrying, which we don't want.
            return [];
        }

        return $summary;
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
            if (in_array($needle, $subArray, true) === true)
            {
                return $key;
            }
        }

        return null;
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
    protected function processReconciliationRequest(array $input)
    {
        // Checks if it's manual call or mailgun call
        if ($this->isManualRequest($input) === true)
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
                'File details are empty.');
        }

        $this->trace->info(
            TraceCode::RECON_FILE_DETAILS,
            $this->allFilesDetails);

        return $this->orchestrate();
    }

    /**
     * Request body, if sent via mail through Mailgun, is too large
     * to be parsed effectively on Splunk. So we unset the body params,
     * then trace everything else.
     * Other headers will be enough to identify the mail if needed.
     *
     * @param array $input Request body
     */
    protected function traceReconRequest(array $input)
    {
        unset($input['body-html']);
        unset($input['body-plain']);
        unset($input['stripped-html']);
        unset($input['stripped-text']);
        unset($input['message-headers']);

        $this->trace->info(
            TraceCode::RECON_REQUEST,
            $input);
    }

    /**
     * Checks if request is manual or via Mailgun.
     *
     * @param array $input The input received from the route.
     * @return boolean Flag to indicate manual request
     */
    protected function isManualRequest(array $input)
    {
        if ((isset($input['manual']) === true) and ($input['manual'] === '1'))
        {
            return true;
        }

        return false;
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
        $this->setGatewayFromEmail();

        $fileLocationType = FileProcessor::UPLOADED;

        if (in_array($this->gateway, self::LINK_BASED_BANKS, true))
        {
            //
            // Fetches the documents from the link, stores them in tmp
            // after extraction if necessary, deletes the zip file, keeping
            // the imp files
            //
            $this->fetchAndStoreLinkDocuments($input);

            $fileLocationType = FileProcessor::STORAGE;

            //
            // This is already being done in `getEmailDetails`, but is being done
            // again here because we create an attachment after parsing the email and
            // downloading the file. Until then, the attachment count would be 0.
            //
            $this->validator->validateAttachments($input);

            $this->emailDetails[self::ATTACHMENT_COUNT] = $input['attachment-count'];
        }

        $allFilesDetails = $this->getFileDetailsFromInput(
            $this->emailDetails, $input, $fileLocationType);

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
            $this->trace->info(
                TraceCode::RECON_FILE_DETAILS,
                [
                    'message'      => 'File details of the file being orchestrated.',
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

        return $this->gatewayReconciliator->startReconciliation($this->allFilesContents);
    }

    protected function checkFileSkip($fileDetails)
    {
        // Checks if this particular file needs to be excluded for the gateway
        $inExclude = $this->gatewayReconciliator->inExcludeList($fileDetails);

        if ($inExclude === true)
        {
            $this->trace->info(
                TraceCode::RECON_FILE_SKIP,
                [
                    'trace_code'   => TraceCode::RECON_FILE_SKIP,
                    'message'      => 'Skipping file because it is present in the exclude list of the gateway.',
                    'file_details' => $fileDetails,
                    'gateway'      => $this->gateway,
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
                    'gateway'      => $this->gateway,
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
            self::GATEWAY          => $input['gateway'],
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
            self::FROM           => strtolower($input['X-Original-Sender'] ?? $input['sender']),
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
    protected function setGatewayFromEmail()
    {
        // For a particular gateway, reconciliation files can be sent from more than one email ID.
        $gateway = $this->getGatewayFromEmail();

        if ($gateway === self::ADMIN)
        {
            $gateway = $this->emailDetails[self::SUBJECT];

            if (in_array($gateway, array_keys(self::GATEWAY_SENDER_MAPPING)) === false)
            {
                throw new Exception\LogicException(
                    '[Admin] Invalid/Unrecognized gateway sent in the subject line.',
                    null,
                    [
                        'gateway'        => $gateway,
                        'valid_gateways' => array_keys(self::GATEWAY_SENDER_MAPPING),
                    ]);
            }
        }

        $this->setGatewayReconciliatorObject($gateway);
    }

    protected function getGatewayFromEmail()
    {
        $fromEmailId = $this->emailDetails[self::FROM];

        $gateway = $this->getKeyFromSubArrayMatch($fromEmailId, self::GATEWAY_SENDER_MAPPING);

        if (empty($gateway) === true)
        {
            throw new Exception\ReconciliationException(
                'Email ID not present in Sender-Gateway mapping.',
                ['email_id' => $fromEmailId]);
        }

        if (($this->gatewayEmailValidationIsNeeded($gateway) === true) and
            ($this->gatewayEmailIsValid($gateway) === false))
        {
            $formattedMailDetails = $this->emailDetails;
            unset($formattedMailDetails[self::BODY]);
            unset($formattedMailDetails[self::BODY_HTML_TEXT]);

            throw new Exception\ReconciliationException(
                'Email content is invalid.',
                [
                    self::EMAIL_DETAILS => $formattedMailDetails
                ]);
        }

        return $gateway;
    }

    protected function gatewayEmailValidationIsNeeded($gateway)
    {
        return (in_array($gateway, self::GATEWAY_EMAIL_VALIDATION, true) === true);
    }

    protected function gatewayEmailIsValid($gateway)
    {
        $gatewayEmailValidator = 'validate' . studly_case($gateway) . 'Email';

        $valid = $this->validator->$gatewayEmailValidator($this->emailDetails);

        return $valid;
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

    protected function getFileDetailsFromAllZipFiles($zipFilesDetails)
    {
        $allExtractedFileDetails = [];

        foreach ($zipFilesDetails as $zipFileDetails)
        {
            try
            {
                $extractedFileDetails = $this->getFileDetailsFromZipFile($zipFileDetails);
            }
            catch (\Exception $ex)
            {
                $level = Trace::ERROR;

                if ($this->gateway === self::AXIS)
                {
                    $level = Trace::INFO;
                }

                $this->trace->traceException(
                    $ex,
                    $level,
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'message'           => 'Unable to extract zip file',
                        'zip_file_details'  => $zipFileDetails,
                        'gateway'           => $this->gateway,
                    ]);

                continue;
            }

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
        $fileType = self::getKeyFromSubArrayMatch(
            $fileDetails[FileProcessor::MIME_TYPE], FileProcessor::FILE_TYPES_MAPPINGS);

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

        $this->gateway = $gateway;

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

        $startRow = $this->gatewayReconciliator->getStartRow($fileDetails);

        // this flag enables us to check if spout lib has been used
        $spoutLib = false;

        if ($fileDetails[FileProcessor::EXTENSION] === Format::XLSX)
        {
            $spoutLib = true;

            // getting contents using spout library for xlsx
            $sheetsContents = $this->converter->getRowsFromExcelSheetsSpout($fileDetails, $sheetNames);
        }
        else
        {
            $sheetsContents = $this->converter->getRowsFromExcelSheetsOptimized($fileDetails, $sheetNames, $startRow);
        }

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
                if ($spoutLib === true)
                {
                    $sheetArray[] = $cellCollection;
                }
                else
                {
                    $sheetArray[] = $cellCollection->all();
                }
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
        $columnHeaders = $this->getColumnHeadersForGatewayIfApplicable($fileDetails);

        $linesToSkip = $this->gatewayReconciliator->getNumLinesToSkip($fileDetails);

        $delimiter = $this->gatewayReconciliator->getDelimiter();

        $csvArray = $this->converter->convertCsvToArray($fileDetails, $columnHeaders, $linesToSkip, $delimiter);

        $this->setExtraDetails($csvArray, $fileDetails);
        $this->allFilesContents[] = $csvArray;
    }

    protected function setExtraDetails(& $arrayContent, $fileDetails)
    {
        $arrayContent[self::EXTRA_DETAILS][FileProcessor::FILE_DETAILS] = $fileDetails;

        $arrayContent[self::EXTRA_DETAILS][self::EMAIL_DETAILS] = $this->emailDetails;
    }

    /**
     * In case of some csv files, the column headers are not present in the csv.
     * These have to be manually defined in the bank reconciliator file.
     *
     * @param $fileDetails
     *
     * @return array
     */
    protected function getColumnHeadersForGatewayIfApplicable($fileDetails)
    {
        $fileName = $fileDetails[FileProcessor::FILE_NAME];

        $reconType = $this->gatewayReconciliator->getReconciliationTypeFromFileName($fileName);

        $columnHeaders = $this->gatewayReconciliator->getColumnHeadersForType($reconType);

        return $columnHeaders;
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

        $use7z = $this->gatewayReconciliator->shouldUse7z($zipFileDetails);

        // unzipFile unzips the file and stores it in a location.
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zipFileDetails, $zipPassword, $use7z);

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

    protected function fetchAndStoreLinkDocuments(array & $input)
    {
        if (empty($input['attachment-count']) === true)
        {
            $input['attachment-count'] = 0;
        }

        $link = $this->gatewayReconciliator->getSettlementFileLink($input['body-html']);

        $this->trace->info(
            TraceCode::RECON_FILE_LINK,
            [
                'link'    => $link,
                'gateway' => $this->gateway,
            ]);

        if ($link === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'   => TraceCode::RECON_FILE_LINK_NOT_FOUND,
                    'message'      => 'Unable to get the link for the MIS file',
                    'gateway'      => $this->gateway,
                ]);

            throw new Exception\ReconciliationException(
                'Unable to get the link',
                [
                    'gateway' => $this->gateway
                ]);
        }

        $file = $this->fileProcessor->getAndStoreFileFromLink($link);

        $attachmentCount = (string) ((int) $input['attachment-count'] + 1);

        $input['attachment-' . $attachmentCount] = $file;
        $input['attachment-count'] = $attachmentCount;
    }

    /**
     * The reconciliation can run for a long time.
     * Hence, changing the system's execution time limit to 1 hour.
     */
    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setTimeLimit(3600);
    }
}
