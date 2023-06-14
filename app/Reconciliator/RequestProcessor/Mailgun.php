<?php

namespace RZP\Reconciliator\RequestProcessor;

use DateTime;
use RZP\Exception;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\FileProcessor;
use RZP\Models\FileStore\Storage\AwsS3\Handler;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Reconciliator\Service;

class Mailgun extends Base
{
    /**************************
     * Email details constants
     **************************/
    const EMAIL_DETAILS   = 'email_details';
    const FROM            = 'from';
    const TO              = 'to';
    const SUBJECT         = 'subject';
    const RECIPIENT       = 'recipient';
    const TIMESTAMP       = 'timestamp';
    const BODY            = 'body';
    const BODY_HTML_TEXT  = 'body_html_text';
    const BODY_HTML       = 'body-html';
    const BODY_PLAIN      = 'body-plain';
    const STRIPPED_HTML   = 'stripped-html';
    const STRIPPED_TEXT   = 'stripped-text';
    const MESSAGE_HEADERS = 'message-headers';
    const X_MAILGUN_SPF   = 'X-Mailgun-Spf';
    const SPF_PASS        = 'pass';

    /**
     * Gateways for which we run validations on email content
     */
    const GATEWAY_EMAIL_VALIDATION = [
        self::HDFC,
        self::AXIS,
        self::KOTAK,
        self::AIRTEL,
        self::PAYZAPP,
        self::HITACHI,
        self::OLAMONEY,
        self::UPI_HDFC,
        self::UPI_HULK,
        self::CARD_FSS_HDFC,
        self::CARD_FSS_SBI,
        self::UPI_ICICI,
        self::FREECHARGE,
        self::FIRST_DATA,
        self::NETBANKING_CSB,
        self::NETBANKING_AXIS,
        self::NETBANKING_BOB,
        self::NETBANKING_ICICI,
        self::NETBANKING_FEDERAL,
        self::VIRTUAL_ACC_KOTAK,
        self::NETBANKING_CORPORATION,
        self::EMANDATE_AXIS,
        self::NETBANKING_ALLAHABAD,
        self::NETBANKING_SBI,
        self::NETBANKING_KVB,
        self::NETBANKING_SVC,
        self::HDFC_DEBIT_EMI,
        self::UPI_JUSPAY,
        self::NETBANKING_JSB,
        self::NETBANKING_FSB,
        self::NETBANKING_JKB,
        self::NETBANKING_IOB,
        self::NETBANKING_DCB,
        self::NETBANKING_DLB,
        self::NETBANKING_RBL,
        self::CARDLESS_EMI_ZESTMONEY,
        self::NETBANKING_BDBL,
        self::NETBANKING_SARASWAT,
        self::NETBANKING_UCO,
        self::EMERCHANTPAY,
    ];

    const LINK_BASED_GATEWAYS = [
        self::FREECHARGE,
    ];

    /**
     * GATEWAY_EMAIL_DETAILS is used to fetch information which is used to upload files in s3
     * This information is provided on gateway level
     * "from" => sender of the mail
     * "subject_pattern" => regex for subject of the mail
     * "filename_pattern" => regex for name of the attachment received
     * "destination" => path on s3 where it will be uploaded
     * "bucket_config_type" => type config that matches to s3 bucket name and region
     */

    const GATEWAY_EMAIL_DETAILS = [
        self::NETBANKING_SBI  => [
            [
                "from" => "donotreply.inb@alerts.sbi.co.in",
                "subject_pattern" => "/(?i)^RAZORPAY Recon file/",
                "filename_pattern" => "/(?i)razorpay_[\d]+\.txt/",
                "destination" => "recon/input/netbanking/SBI/bank_payment_report/",
                "bucket_config_type" => FileStore\Type::RECON_AUTOMATIC_FILE_FETCH
            ]
        ],
        self::WALLET_BAJAJ => [
            [
                "from" => "kishor.kangune@bajajfinserv.in",
                "subject_pattern" => "/(?i)^RZP MID BFL0000001675590 Settlement Data(.+)?/",
                "filename_pattern" => "/(?i)^RZP MID BFL0000001675590 Settlement Data(.+)?/",
                "destination" => "recon/input/WALLET_BAJAJ/transaction_report/",
                "bucket_config_type" => FileStore\Type::RECON_AUTOMATIC_FILE_FETCH
            ]
        ]
    ]; 

    protected $inputDetails;

    /**
     * Getting all files details is handled by this function when the
     * reconciliation route is hit by MailGun.
     *
     * @param array $input The input received from the route.
     * @return array Details of all the files received from the input.
     */
    public function process(array $input): array
    {
        // Gets the email details and validates the email details.
        $this->inputDetails = $this->getEmailDetails($input);

        $this->validator->filterEmails($this->inputDetails);

        // Figures out the gateway and sets the gateway reconciliator object for
        // the orchestrator, using the input details.
        $this->setGatewayFromEmail();

        $fileLocationType = FileProcessor::UPLOADED;

        if (in_array($this->gateway, self::LINK_BASED_GATEWAYS, true))
        {
            $this->fetchAndStoreLinkDocuments($input);

            $fileLocationType = FileProcessor::STORAGE;

            //
            // This is already being done in `getEmailDetails`, but is being done
            // again here because we create an attachment after parsing the email and
            // downloading the file. Until then, the attachment count would be 0.
            //
            $this->validator->validateAttachments($input);

            $this->inputDetails[self::ATTACHMENT_COUNT] = $input[self::ATTACHMENT_HYPHEN_COUNT];
        }

        $this->inputDetails[self::SOURCE] = self::MAILGUN;

        $allFilesDetails = $this->getFileDetailsFromInput(
            $this->inputDetails, $input, $fileLocationType);

        $gateway = $this->gateway;

        $emailRecipient = $this->inputDetails[self::TO];

        /**
         * if gateway is added in the config then fetch details of file and call the s3 bucket upload flow
         */

        if($this->validator->isAutomaticFetchingEnabledForGateway($gateway, $emailRecipient)){

            $date = new DateTime();
            $result = $date->modify("-1 days")->format('Y-m-d');

            foreach($allFilesDetails as $fileDetails){
                $fileName = $fileDetails['file_name'];
                $filePath = $fileDetails['file_path'];
                $extension = $fileDetails['extension'];
                
                $gatewayDetails = self::GATEWAY_EMAIL_DETAILS[$gateway] ?? null;
                if(is_null($gatewayDetails)){
                    $this->trace->info(TraceCode::GATEWAY_EMAIL_CONFIG_NOT_ENABLED, [
                        "message" => "Email config details not found for gateway"
                    ]);
                    continue;
                }
                foreach($gatewayDetails as $detail){
                    if(preg_match($detail['subject_pattern'], $input['subject']) && preg_match($detail['filename_pattern'], $fileName)){
                        $destinationPath = $detail['destination'].$result."/".$fileName;
                        $bucketConfigType = $detail['bucket_config_type'];
                        $this->automaticFileFetchUpload($filePath, $destinationPath, $extension, $fileName, $bucketConfigType);
                        break;
                    }
                }
                
            }

        }

        return [
            self::FILE_DETAILS  => $allFilesDetails,
            self::INPUT_DETAILS => $this->inputDetails,
        ];
    }

    public function automaticFileFetchUpload($filePath, $destinationPath, $extension, $fileName, $bucketConfigType) 
    {
        $creator = new FileStore\Creator;

        $this->trace->info(TraceCode::ART_RECON_BUCKET_UPLOAD_DETAILS,[
            'filePath' => $filePath,
            '$destinationPath' => $destinationPath,
            'extension' => $extension,
            '$fileName' => $fileName,
            'bucketConfigType' => $bucketConfigType
        ]);

        try
        {
            $creator->localFilePath($filePath)
                    ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$extension][0])
                    ->name($destinationPath) 
                    ->type($bucketConfigType)
                    ->extension($extension)
                    ->additionalParameters(['ACL' => 'bucket-owner-full-control']);

            $fileStoreEntity = $creator->save()->get();

            $this->trace->info(TraceCode::RECON_ART_FILE_UPLOAD_SUCCESSFUL, $fileStoreEntity);
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_FILE_UPLOAD_FAILURE,
                [
                    'file_name' => $fileName,
                    'gateway'   => $this->gateway,
                    'exception' => $ex
                ]);

            // Delete local file and return.
            (new FileProcessor)->deleteFileLocally($filePath);
            return;
        }

        $traceData = [
            'file_id'   => $fileStoreEntity['id'],
            'file_name' => $fileStoreEntity['name'],
            'gateway'   => $this->gateway,
        ];

        $this->trace->info(TraceCode::RECON_ART_FILE_DETAILS, $traceData);

        return;
    }

    public function processForVa(array $input): array
    {
        // Gets the email details and validates the email details.
        $inputDetails = $this->getEmailDetails($input);

        $fileLocationType = FileProcessor::UPLOADED;

        $inputDetails[self::SOURCE] = self::MAILGUN;

        $allFilesDetails = $this->getFileDetailsFromInput(
            $inputDetails, $input, $fileLocationType);

        return [
            self::FILE_DETAILS  => $allFilesDetails,
            self::INPUT_DETAILS => $inputDetails,
        ];
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

        $strippedHtml = $input[self::STRIPPED_HTML] ?? '';

        if($input[self::RECIPIENT] == "finances.recon@mg.razorpay.com")
        {
            $from = $input['X-Original-Sender'] ?? $input['From'];
        }
        else
        {
            $from = $input['X-Original-Sender'] ?? $input['sender'];
        }
        
        $inputDetails = [
            self::FROM           => strtolower($from),
            self::SUBJECT        => $input[self::SUBJECT],
            self::TO             => $input[self::RECIPIENT],
            self::TIMESTAMP      => $input[self::TIMESTAMP],
            self::BODY           => $input[self::STRIPPED_TEXT] ?? '',
            self::BODY_HTML_TEXT => html_entity_decode(strip_tags($strippedHtml)),
        ];

        //
        // Validates that attachments are present in the email.
        // In some cases (link based banks), attachment count can be 0.
        // We haven't parsed the email for attachments yet at this point.
        // Hence, sending `true` as the second parameter (allowZeroAttachments).
        //
        $this->validator->validateAttachments($input, true);

        $inputDetails[self::ATTACHMENT_COUNT] = $input[self::ATTACHMENT_HYPHEN_COUNT];

        return $inputDetails;
    }

    /**
     * Uses the 'from' email ID to figure out the gateway.
     * If 'from' email ID is of one of the whitelisted admins,
     * it uses the 'subject' to figure out the gateway.
     * It also sets the gateway reconciliator object for the class.
     *
     * @throws Exception\LogicException
     */
    protected function setGatewayFromEmail()
    {
        // For a particular gateway, reconciliation files can be sent from more than one email ID.
        $this->gateway = $this->getGatewayFromEmail();

        if ($this->gateway === self::ADMIN)
        {
            $this->gateway = $this->inputDetails[self::SUBJECT];

            if (in_array($this->gateway, array_keys(self::GATEWAY_SENDER_MAPPING)) === false)
            {
                throw new Exception\LogicException(
                    '[Admin] Invalid/Unrecognized gateway sent in the subject line.',
                    null,
                    [
                        'gateway'        => $this->gateway,
                        'valid_gateways' => array_keys(self::GATEWAY_SENDER_MAPPING),
                    ]);
            }
        }

        if(in_array($this->inputDetails[self::TO], Service::BLACKLISTED_EMAIL_FOR_API_AUTO_RECON_VIA_MAILGUN)){
            return;
        }

        $this->setGatewayReconciliatorObject();
    }

    protected function getGatewayFromEmail()
    {
        $fromEmailId = $this->inputDetails[self::FROM];

        $gateway = get_key_from_subarray_match($fromEmailId, self::GATEWAY_SENDER_MAPPING);

        if (empty($gateway) === true)
        {
            throw new Exception\ReconciliationException(
                'Email ID not present in Sender-Gateway mapping.',
                ['email_id' => $fromEmailId]);
        }

        if (($this->gatewayEmailValidationIsNeeded($gateway) === true) and
            ($this->gatewayEmailIsValid($gateway) === false))
        {
            $formattedMailDetails = $this->inputDetails;
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

    protected function gatewayEmailValidationIsNeeded($gateway): bool
    {
        return (in_array($gateway, self::GATEWAY_EMAIL_VALIDATION, true) === true);
    }

    protected function gatewayEmailIsValid($gateway)
    {
        $gatewayEmailValidator = 'validate' . studly_case($gateway) . 'Email';

        $valid = $this->validator->$gatewayEmailValidator($this->inputDetails);

        return $valid;
    }

}
