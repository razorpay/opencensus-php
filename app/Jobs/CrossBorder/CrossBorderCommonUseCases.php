<?php

namespace RZP\Jobs\CrossBorder;

use App;
use Mail;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Jobs\Job;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Payment\Constant;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Models\Feature;
use RZP\Models\Invoice\Status;
use RZP\Http\Request\Requests;
use RZP\Constants\Entity as E;
use RZP\Base\RepositoryManager;
use RZP\Models\Transaction\Type;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Merchant\Document;
use RZP\Models\Invoice\Constants;
use RZP\Models\Payment;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Merchant\EsDisabledNotify;
use RZP\Models\Invoice\DccEInvoiceCore;
use RZP\Mail\Merchant as MerchantEmail;
use RZP\Models\Merchant\HsCode\HsCodeList;
use RZP\Models\Invoice\Type as InvoiceType;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Invoice\Service as InvoiceService;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\Merchant\Attribute\Service as AttributeService;
use RZP\Models\Workflow\Service\Client as WorkflowServiceClient;
use RZP\Models\Merchant\InternationalIntegration\Service as MIIService;
use RZP\Models\Settlement\Processor\OPGSPImportICICI\Processor as OpgspIciciProcessor;
use RZP\Models\Merchant\InternationalIntegration\Service as MerchantInternationalIntegrationService;


class CrossBorderCommonUseCases extends Job
{
    const MODE = 'mode';

    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 300;

    // constants for OPGSP import flow processing
    const OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT      = 'OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT';
    const OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT_BULK = 'OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT_BULK';
    const OPGSP_IMPORT_GENERATE_SETTLEMENT_FILE      = 'OPGSP_IMPORT_GENERATE_SETTLEMENT_FILE';
    const OPGSP_IMPORT_SEND_INVOICES                 = 'OPGSP_IMPORT_SEND_INVOICES';
    const DEFAULT_DAYS_FOR_BULK_CLEAR = 15;

    // constants for OPGSP import invoice reminder
    const OPGSP_IMPORT_INVOICE_REMINDER = 'OPGSP_IMPORT_INVOICE_REMINDER';
    const DEFAULT_BUSINESS_NAME = 'Team';
    const DEFAULT_MONTH_YEAR = 'last';
    const DEFAULT_PREV_DAYS = 15;

    // constants for FIRS available email
    const FIRS_AVAILABLE_NOTIFICATION = 'FIRS_AVAILABLE_NOTIFICATION';
    const UPLOADED = 'uploaded';
    const CREATED = 'created';

    // constants for FIRS processing
    const ZIP_FIRS_DOCUMENTS = 'ZIP_FIRS_DOCUMENTS';
    const FIRS_ICICI_FILE = 'firs_icici_file';
    const FIRS_ICICI_ZIP  = 'firs_icici_zip';
    const BATCH_SIZE      = 6000;

    // constants for DCC E-Invoice
    const GENERATE_DCC_E_INVOICE = 'GENERATE_DCC_E_INVOICE';

    const INTL_BANK_TRANSFER_SWIFT_SETTLEMENT = 'INTL_BANK_TRANSFER_SWIFT_SETTLEMENT';
    const MERCHANT_ONBOARD_NETWORK = 'MERCHANT_ONBOARD_NETWORK';
    const EMERCHANTPAY_ONBOARDING_VIA_MAF = 'EMERCHANTPAY_ONBOARDING_VIA_MAF';
    const CREATE_INVOICE_VERIFICATION_WORKFLOW = 'CREATE_INVOICE_VERIFICATION_WORKFLOW';

    const DISABLE_ON_DEMAND_SETTLEMENT = 'DISABLE_ON_DEMAND_SETTLEMENT';

    const UPDATE_PAYMENT_STATUS = 'update_payment_status';
    /**
     * @var string
     */
    protected $queueConfigKey = 'cross_border_use_case';

    /**
     * @var array
     */
    protected $payload;

    protected $mode;

    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    public $timeout = 900;

    public function __construct(array $payload)
    {
        $this->setMode($payload);

        parent::__construct($this->mode);

        $this->payload = $payload;
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_INIT,[
                'payload'  => $this->payload,
            ]);

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];

            $action = $this->payload['action'];

            switch($action)
            {
                case self::UPDATE_PAYMENT_STATUS:
                    $this->app['payments-cross-border']->updatePaymentStatus($this->payload['body']);
                    break;
                case self::GENERATE_DCC_E_INVOICE:
                    $this->generateEInvoice();
                    break;
                case self::INTL_BANK_TRANSFER_SWIFT_SETTLEMENT:
                    (new BankTransfer\Service())->settlementFromCurrencyCloud($this->payload['body']);
                    break;
                case self::MERCHANT_ONBOARD_NETWORK:
                    (new AttributeService())->onboardMerchantOnNetworks($this->payload['body']);
                    break;
                case self::EMERCHANTPAY_ONBOARDING_VIA_MAF:
                    (new MerchantInternationalIntegrationService())->generateEmerchantpayMaf($this->mode, $this->payload['body']['merchant_id']);
                    break;
                case self::CREATE_INVOICE_VERIFICATION_WORKFLOW:
                    $this->createInvoiceVerificationWorkflow();
                    break;
                case self::OPGSP_IMPORT_INVOICE_REMINDER:
                    $this->sendInvoiceReminderEmailForOpgspImport();
                    break;
                case self::FIRS_AVAILABLE_NOTIFICATION:
                    $this->sendFIRSAvailableToDownloadEmail();
                    break;
                // Triggers from
                // 1. Single upload of invoice -> payment/{id}/update_merchant_doc
                // 2. Bulk upload of invoice -> payment/merchant_documents
                // 3. Bulk clear cron job -> import/transactions/onhold/clear
                case self::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT:
                    $this->opgspOnHoldClear();
                    break;
                // Triggers from cron job endpoint -> import/transactions/onhold/clear
                case self::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT_BULK:
                    $this->opgspBulkOnHoldClear();
                    break;
                // Triggers from cron job endpoint -> settlements/import/generate
                case self::OPGSP_IMPORT_GENERATE_SETTLEMENT_FILE:
                    $this->generateSettlementFile();
                    break;
                // Triggers from cron job endpoint -> import/invoices/send
                case self::OPGSP_IMPORT_SEND_INVOICES:
                    $this->sendInvoices();
                    break;
                case self::ZIP_FIRS_DOCUMENTS:
                    $this->zipFIRS();
                    break;
                case self::DISABLE_ON_DEMAND_SETTLEMENT:
                    $this->disableODSForOpgspMerchant($this->payload['mode'],$this->payload['merchant_id']);
                    break;
                default:
                    $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_INVALID_ACTION,[
                        'payload'  => $this->payload,
                        'message'  => 'invalid action provided'
                    ]);
            }

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_COMPLETED,[
                'payload'  => $this->payload,
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CROSS_BORDER_COMMON_USE_CASES_FAILED,[
                    'payload' => $this->payload,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $this->release($workerRetryDelay);

            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_RELEASED, [
                'payload'               => $this->payload,
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);

            // Push Error Metrics to Vajra for failed cases
            (new Metrics())->pushErrorMetrics(Metrics::CROSS_BORDER_COMMON_WORKER_JOB_FAILED, [
                Metrics::ACTION => $this->payload['action'],
                Metrics::IS_DELETED => false
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DELETED, [
                'payload'           => $this->payload,
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            // Push Error Metrics to Vajra for Deleted cases
            (new Metrics())->pushErrorMetrics(Metrics::CROSS_BORDER_COMMON_WORKER_JOB_FAILED, [
                Metrics::ACTION => $this->payload['action'],
                Metrics::IS_DELETED => true
            ]);
        }
    }

    /**
     * Set mode for job.
     * @param $payload array
     *
     * @return void
     */
    protected function setMode(array $payload): void
    {
        if (array_key_exists(self::MODE, $payload) === true)
        {
            $this->mode = $payload[self::MODE];
            $this->app['rzp.mode'] = $payload[self::MODE];
        }
        else {
            $this->mode = Mode::LIVE;
            $this->app['rzp.mode'] = Mode::LIVE;
        }
    }

    protected function createInvoiceVerificationWorkflow()
    {
        $response = (new WorkflowServiceClient)->createWorkflowProxy($this->payload['body']);
        if ($this->payload['priority'] == 'P0') {
            try
            {
                CrossBorderCommonUseCases::sendSlackNotification(
                    $this->payload['payment_id'],
                    $this->payload['merchant_id'],
                    $this->payload['priority'],
                    $response['id'],
                    "");
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::CROSS_BORDER_INVOICE_WORKFLOW_NOTIFICATION_FAILED,
                    [
                        'payload' => $this->payload,
                    ]
                );
            }
        }
    }

    public static function sendSlackNotification($paymentId, $merchantId, $priority, $workflowId, $state) {
        $dasboardUrl = app('config')->get('applications.workflows.cross_border.invoice_verification_dashboard_domain');
        $webhookUrl = app('config')->get('slack.endpoint');
        $channel = app('config')->get('slack.channels.cb_invoice_verification_alerts');
        $payload = [
            "channel" => $channel,
            "username" => "cb-invoice-verification-alerts",
            "icon_emoji" => ":slack:",
            "blocks" => [
                [
                    "type" => "header",
                    "text" => [
                        "type" => "plain_text",
                        "text" => "Invoice Verifcation Workflow Request ".$state,
                        "emoji" => true
                    ]
                ],
                [
                    "type" => "section",
                    "fields" => [
                        [
                            "type" => "mrkdwn",
                            "text" => "*PaymentId:*  ".$paymentId
                        ],
                        [
                            "type" => "mrkdwn",
                            "text" => "*Priority:* ".$priority
                        ],
                        [
                            "type" => "mrkdwn",
                            "text" => "*MerchantId:* ".$merchantId
                        ],
                        [
                            "type" => "mrkdwn",
                            // @cb-invoice user-groupId: S05CGB5G36G
                            "text" => "*Owner:* <!subteam^S05CGB5G36G>"
                        ]
                    ]
                ],
                [
                    "type" => "actions",
                    "elements" => [
                        [
                            "type" => "button",
                            "text" => [
                                "type" => "plain_text",
                                "text" => "View Workflow :rocket:",
                                "emoji" => true
                            ],
                            "style" => "primary",
                            "value" => "click_me_123",
                            "action_id" => "actionId-0",
                            "url" => $dasboardUrl.$workflowId,
                        ]
                    ]
                ]
            ]
        ];
        $response = Requests::request($webhookUrl, [], json_encode($payload), "POST");
    }

    protected function sendInvoiceReminderEmailForOpgspImport()
    {
        $merchantId = $this->payload['merchant_id'];

        // fetch merchant detail entity to get business name and contact email
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        $contactEmail = $merchantDetail->getContactEmail();
        if (!isset($contactEmail) or empty($contactEmail))
        {
            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DELETED, [
                'payload'           => $this->payload,
                'message'           => 'Deleting the job as contact email address is not present.'
            ]);
            return;
        }
        $businessName = $merchantDetail->getBusinessName();

        $prev_days = $input['prev_days'] ?? self::DEFAULT_PREV_DAYS;

        // get payments count/amount from last 15 days with on_hold true
        $startTime = Carbon::now()->subDays($prev_days)->getTimestamp();
        $endTime = Carbon::now()->getTimestamp();

        $transactions = $this->repo->transaction
            ->getCountAndAmountByMerchantAndOnholdAndTypes($merchantId, true, [Type::PAYMENT], $startTime, $endTime);

        if (isset($transactions) and
            isset($transactions['count']) and
            $transactions['count'] > 0) {

            $mailPayload = [
                'business_name' => (isset($businessName) and !empty($businessName)) ? $businessName : self::DEFAULT_BUSINESS_NAME,
                "contact_email" => $contactEmail,
                "count" => $transactions['count'],
                "total_credit" => $transactions['total_credit']/100,

            ];

            $mail = new MerchantEmail\MerchantInvoiceReminderMail($mailPayload);
            Mail::Send($mail);
        }
    }

    protected function sendFIRSAvailableToDownloadEmail()
    {
        // get merchant document entity and merchant id
        $documentId = $this->payload['document_id'];
        $document = $this->repo->merchant_document->findDocumentById($documentId);
        $merchantId = $document->getMerchantId();

        $fileDetails = $this->app['ufh.service']->getFileDetails($document->getPublicFileStoreId(), $merchantId);

        // check file upload status
        if ($fileDetails['status'] != self::UPLOADED)
        {
            // retry if file upload status is 'created'
            if ($fileDetails['status'] == self::CREATED)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_DOCUMENT_UPLOAD_OPERATION_IN_PROGRESS);
            }
            else
            {
                $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DELETED, [
                    'payload'           => $this->payload,
                    'message'           => 'Deleting the job as FIRS was not successfully uploaded.'
                ]);
            }
            return;
        }

        // fetch merchant detail entity to get business name and contact email
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        $merchant = $this->repo->merchant->find($merchantId);

        $contactEmail = $merchantDetail->getContactEmail();
        if (!isset($contactEmail) or empty($contactEmail))
        {
            $this->trace->info(TraceCode::CROSS_BORDER_COMMON_USE_CASES_DELETED, [
                'payload'           => $this->payload,
                'message'           => 'Deleting the job as contact email address is not present.'
            ]);
            return;
        }

        $businessName = $merchantDetail->getBusinessName();
        $documentDate = $document->getDocumentDate();

        $mailPayload = [
            'business_name' => (isset($businessName) and !empty($businessName)) ? $businessName : self::DEFAULT_BUSINESS_NAME,
            'contact_email' => $contactEmail,
            'firs_month_year' => (isset($documentDate) and !empty($documentDate)) ? date("M Y", $documentDate) : self::DEFAULT_MONTH_YEAR,
            'org_id' => $merchant->getOrgId()
        ];

        $mail = new MerchantEmail\FirsAvailableMail($mailPayload);
        Mail::Send($mail);
    }

    protected function opgspOnHoldClear()
    {
        $input = $this->payload;

        $merchantId = $input['merchant_id'];
        $paymentId  = $input['payment_id'];
      //  $hscode = $input['hscode'] ?? null;

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $purpose_code=$merchant->getPurposeCode();

        if(($merchant->isOpgspImportSettlementEnabled() === false) and
           ($merchant->isJpmcImportFlowEnabled() === false))
        {
            $this->trace->info(TraceCode::IMPORT_FLOW_MISSING_RISK_VALIDATION, [
                'input'      => $input,
            ]);

            return;
        }

        $AwbCheckRequiredCodes = Constant::OPGSP_AWB_REQUIRED;
        $isAwbCheckRequired=in_array($purpose_code, $AwbCheckRequiredCodes);

        $paymentSupportingDocuments = (new InvoiceService())->findByPaymentId($paymentId);

        $payment = $this->repo->payment->findOrFail($paymentId);

        if($payment->isCaptured() === false)
        {
            return;
        }

        $isAwbUploaded = false;

        foreach($paymentSupportingDocuments as $document)
        {
//            if ($document[InvoiceEntity::TYPE] === InvoiceType::OPGSP_INVOICE and
//                isset($document[InvoiceEntity::REF_NUM]))
//            {
//                $isInvoiceUploaded = true;
//            }

//            if ($document[InvoiceEntity::TYPE] === InvoiceType::JPMC_INVOICE and
//                isset($document[InvoiceEntity::REF_NUM]))
//            {
//                $isInvoiceUploaded = true;
//            }

            if ($document[InvoiceEntity::TYPE] === InvoiceType::OPGSP_AWB and
                isset($document[InvoiceEntity::REF_NUM]))
            {
                $isAwbUploaded = true;
            }
        }
        if ($isAwbCheckRequired === true and $isAwbUploaded === false)
        {
            return;
        }

        $transaction = $this->repo->transaction->findByEntityId($paymentId, $merchant);

        try
        {
            $txn = $this->setOnHoldFalse($transaction->getId(), $payment);
            $successTxnIds[] = $txn->getId();

            $this->dispatchForSettlement($txn, $successTxnIds);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IMPORT_FLOW_ON_HOLD_CLEAR_FAILED
            );

            throw $e;
        }
    }

    /*
     * This method is used by cron to fetch all on hold transactions
     * in last 15 days, and enable them for settlement if they are eligible.
     */
    protected function opgspBulkOnHoldClear()
    {
        $input = $this->payload;
        $merchantId = $input['merchant_id'];
        $prevDays = $input['prev_days'] ?? self::DEFAULT_DAYS_FOR_BULK_CLEAR;

        $hscode = (new MIIService())->getMerchantHsCode($merchantId);

        if(isset($hscode) === false)
        {
            $this->trace->info(TraceCode::INVALID_HS_CODE_FOR_MERCHANT, [
                'input'           => $input,
                'hscode'          => $hscode,
            ]);
            return;
        }

        // get payments from last 15 days with on_hold true
        $startTime = Carbon::now()->subDays($prevDays)->getTimestamp();
        $endTime = Carbon::now()->getTimestamp();

        $transactions = $this->repo->transaction
            ->getTransactionsByMerchantAndOnholdAndTypes($merchantId, true, [Type::PAYMENT], $startTime, $endTime, 1000);

        foreach ($transactions as $transaction)
        {
            $data = [
                'merchant_id'   => $merchantId,
                'payment_id'    => $transaction[TransactionEntity::ENTITY_ID],
                'hscode'        => $hscode,
                'action'        => CrossBorderCommonUseCases::OPGSP_IMPORT_CLEAR_ON_HOLD_SETTLEMENT,
            ];

            CrossBorderCommonUseCases::dispatch($data)->delay(rand(60, 1000) % 601);
        }
    }

    private function setOnHoldFalse($transactionId, $payment): Transaction\Entity
    {
        $isExpEnabled = (new Payment\Service)->checkIfTransactionOnholdWriteRemovalEnabled($payment->merchant);

        if($isExpEnabled===false)
        {
            $result = $this->repo->transaction(function () use ($transactionId) {
                $txn = $this->repo->transaction->lockForUpdate($transactionId);

                $txn->setOnHold(false);

                $this->repo->saveOrFail($txn);

                return $txn;
            });
        }
        else
        {
            $txn = (new Payment\Service())->createVirtualPaymentTxnFromLedger($payment);

            $txn->setOnHold(false);
            
            return $txn;
        }

        return $result;
    }

    private function dispatchForSettlement($txn, $successTxnIds)
    {
        try
        {
            $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

            $txn->setSettledAt($settledAt);

            $bucketCore = new Bucket\Core;

            $balance = $txn->accountBalance;

            $newService = $bucketCore->shouldProcessViaNewService($txn->getMerchantId(), $balance);

            if ($newService === true)
            {
                $bucketCore->settlementServiceToggleTransactionHold($successTxnIds, null);
            }
            else
            {
                (new Transaction\Core)->dispatchForSettlementBucketing($txn);
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::IMPORT_FLOW_DISPATCH_SETTLEMENT_FAILED
            );
            throw $e;
        }

    }

    protected function generateSettlementFile()
    {
        (new OpgspIciciProcessor())->generateSettlementFileForICICIOpgspImport($this->payload);
    }

    protected function sendInvoices()
    {
        (new OpgspIciciProcessor())->sendInvoicesForICICIOpgspImport($this->payload);
    }

    protected function zipFIRS()
    {
        $input = [
            'merchant_id'   => $this->payload['merchant_id'],
            'month'         => $this->payload['month'],
            'year'          => $this->payload['year'],
            'force_create'  => $this->payload['force_create']
        ];

        $ufhService = $this->app['ufh.service'];

        $merchantId = $input['merchant_id'];
        $month = $input['month'];
        $year = $input['year'];

        $this->deleteExistingZipFilesIfExists($merchantId, $month, $year);

        $from = strtotime($month.'/01/'.$year);
        $to = strtotime("+1 Month",$from)-1;

        $documents = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,self::FIRS_ICICI_FILE,$from,$to);

        $fileIds=[];

        foreach ($documents as $file)
        {
            array_push($fileIds,$file->getPublicFileStoreId());
        }

        $batches = array_chunk($fileIds,self::BATCH_SIZE);

        foreach($batches as $batch)
        {
            $prefix = "Firs";

            $zipFileId = $ufhService->downloadFiles($batch,$merchantId,$prefix,self::FIRS_ICICI_ZIP);

            $this->trace->info(TraceCode::BULK_DOWNLOAD,[
                'success' => isset($zipFileId),
            ]);

            $response = ['id' => $zipFileId];

            $documentDate = strtotime($input['month'].'/'.date('d').'/'.$input['year']);

            $document = (new Document\Core)->saveInMerchantDocument($response,$merchantId,self::FIRS_ICICI_ZIP,$documentDate);
        }

        if (isset($document))
        {
            try
            {
                // set the mode
                if (isset($this->app['rzp.mode']) === false)
                {
                    $this->app['rzp.mode'] = $this->mode ?? Mode::LIVE;
                }
                $data = [
                    'document_id' => $document->getId(),
                    'action'      => CrossBorderCommonUseCases::FIRS_AVAILABLE_NOTIFICATION,
                    'mode'        => $this->app['rzp.mode'],
                ];
                $this->trace->info(TraceCode::FIRS_SEND_EMAIL_MESSAGE_DISPATCHED,
                    [
                        'data' => $data,
                    ]
                );
                // adding delay of 10 to 15 minutes for the ZIP creation
                CrossBorderCommonUseCases::dispatch($data)->delay(600 + rand(0, 1000) % 301);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::FIRS_SEND_EMAIL_MESSAGE_DISPATCH_FAILED,
                    [
                        'document_id' => $document->getId()
                    ]);
            }
        }

        return ['merchant_id' => $merchantId, 'success' => isset($document)];
    }

    /*
    * Deleting only ICICI FIRS ZIP Files
    */
    private function deleteExistingZipFilesIfExists(string $merchantId,$month,$year)
    {
        $from = strtotime($month.'/01/'.$year);
        $to = strtotime("+1 Month",$from)-1;

        $ufhService = $this->app['ufh.service'];

        $documentEntities = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,self::FIRS_ICICI_ZIP,$from,$to);

        foreach($documentEntities as $documentEntity)
        {
            if ($documentEntity != null)
            {
                $this->trace->info(TraceCode::DELETE_EXISTING_ZIPPED_FIRS_DOCUMENTS, [
                    'file_store_id' => $documentEntity->getPublicFileStoreId()
                ]);

                $ufhService->deleteFile($documentEntity->getPublicFileStoreId(),$merchantId,self::FIRS_ICICI_ZIP);
                (new Document\Core)->deleteDocument($documentEntity->getFileStoreId());
            }
        }
    }

    protected function generateEInvoice()
    {
        try
        {
            $requestId = $this->payload[Constants::REFERENCE_ID];
            $referenceType = $this->payload[Constants::REFERENCE_TYPE];

            // get payment and refund entity using id of the payload
            $paymentId = $requestId;
            $refund = null;
            $baseEntity = null;
            if ($referenceType === Constants::REFUND_FLOW) {
                $refund = $this->repo->refund->findOrFail($requestId);
                $paymentId = $refund->getPaymentId();
                $baseEntity = $refund;
            }
            $payment = $this->repo->payment->findOrFail($paymentId);
            if (!isset($baseEntity)) $baseEntity = $payment;

            // if payment is not DCC then do not process request
            if ($payment->isDCC() === false) {
                $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED, [
                    'message' => 'requested payment is not a DCC payment',
                ]);

                return;
            }
            // validate the existing invoices if present
            $paymentEInvoices = $this->repo->invoice->fetchInvoicesByEntity($paymentId, E::PAYMENT);
            // this will store the existing e-invoice in case it was not generated earlier (failed state)
            $existingPaymentEInvoice = null;

            if ($referenceType === Constants::REFUND_FLOW) {
                if (count($paymentEInvoices) === 0) {
                    // INV should be present for CRN request
                    $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED, [
                        'message' => 'INV not found for the provided CRN request',
                        'payload' => $this->payload,
                    ]);

                    return;
                }
                foreach ($paymentEInvoices as $eInvoice) {
                    if ($eInvoice->getType() === Constants::REFERENCE_TYPE_TO_TYPE_MAP[$referenceType] and $eInvoice->getRefNum() === $refund->getId()) {
                        if ($eInvoice->getStatus() !== Status::GENERATED) {
                            $existingPaymentEInvoice = $eInvoice;
                            break;
                        }
                        // do not process duplicate request of CRN
                        $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED, [
                            'message' => 'CRN already exist for given refund id',
                            'payment_e_invoice_id' => $eInvoice->getId(),
                            'payload' => $this->payload,
                        ]);

                        return;
                    }
                }
            } else if (count($paymentEInvoices) > 0) {
                foreach ($paymentEInvoices as $eInvoice) {
                    if ($eInvoice->getType() === Constants::REFERENCE_TYPE_TO_TYPE_MAP[$referenceType]) {
                        if ($eInvoice->getStatus() !== Status::GENERATED) {
                            $existingPaymentEInvoice = $eInvoice;
                            break;
                        }
                        // do not process duplicate request of IVN
                        $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED, [
                            'message' => 'INV already exist for given payment id',
                            'payment_e_invoice_id' => $eInvoice->getId(),
                            'payload' => $this->payload,
                        ]);

                        return;
                    }
                }
            }

            $eInvoiceCore = new DccEInvoiceCore();

            // if invoice already exist then fetch the object from master DB
            // this is required as writes can't be done on slave DB
            // if the invoice already exist then $existingPaymentEInvoice will be the read object (from slave)
            // which we fetched using entity_id field. (indexed on slave only)
            // we'll fetch write object from master's DB using the invoice ID (Primary key i.e. always indexed)
            if (isset($existingPaymentEInvoice) and !empty($existingPaymentEInvoice)) {
                $paymentEInvoice = $eInvoiceCore->getEntityFromMaster($existingPaymentEInvoice->getId());
            } else {
                $paymentEInvoice = $eInvoiceCore->createNewDCCEInvoice($payment, $baseEntity, $referenceType);
            }

            // fetch request payload for registering invoice
            $requestData = $eInvoiceCore->getEInvoiceRequestData($paymentEInvoice, $payment, $baseEntity);
            if (empty($requestData)) {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::BUILDING_REQUEST_DATA_FAILED);
                return;
            }

            // update status to initiated
            $eInvoiceCore->updateStatusAndError($paymentEInvoice, Status::INITIATED);

            // request to Masters India to register invoice
            $response = $eInvoiceCore->registerDCCInvoice($requestData, $this->mode);
            if (!isset($response) or empty($response[Constants::IRN])) {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::INVOICE_REGISTRATION_FAILED, $response);
                return;
            }

            // update entity with response data
            $eInvoiceCore->updateEInvoiceEntity($paymentEInvoice, $response);

            // generate E-Invoice PDF
            $pathToFile = $eInvoiceCore->generateInvoicePDF($payment, $requestData, $response);
            if (empty($pathToFile)) {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::INVOICE_PDF_GENERATION_FAILED);
                return;
            }

            // upload to S3 using UFH
            $fileAccessUrl = $eInvoiceCore->uploadInvoiceToUFH($pathToFile, $baseEntity, $payment, $referenceType);
            if (!isset($fileAccessUrl)) {
                $this->handleFailure($paymentEInvoice, $eInvoiceCore, Constants::INVOICE_UPLOAD_FAILED);
                return;
            }

            // delete local invoice
            $eInvoiceCore->deleteLocalFile($pathToFile);

            // update status to generated
            $eInvoiceCore->updateStatusAndError($paymentEInvoice, Status::GENERATED);

            $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_COMPLETED, [
                'payment_e_invoice_id' => $paymentEInvoice->getId(),
                'payload' => $this->payload,
            ]);
        }
        catch (\Throwable $e)
        {
            throw $e;
        }
    }

    private function handleFailure($paymentEInvoice, $eInvoiceCore, $errorCode = Constants::INVOICE_CREATION_FAILED, $data = [])
    {
        $this->trace->info(TraceCode::PAYMENT_E_INVOICE_JOB_FAILED,[
            'error_code'  => $errorCode,
            'data' => $data,
            'payment_e_invoice_id' => $paymentEInvoice->getId(),
            'payload'  => $this->payload,
        ]);

        // update status to failed with error code
        $eInvoiceCore->updateStatusAndError($paymentEInvoice, Status::FAILED, $errorCode);

        if (in_array($errorCode, Constants::NON_RETRYABLE_ERROR_CODES) == false)
        {
            throw new BadRequestException($errorCode);
        }
    }

    private function disableODSForOpgspMerchant($mode, $merchantId)
    {
        if ($mode === Mode::TEST)
        {
            return;
        }

        $merchant = $this->repo->merchant->findOrFail($merchantId);
        $isFeatureDisabled = false;
        $featuresDisabled = [];

        foreach (Feature\Constants::EARLY_SETTLEMENT_FEATURES as $feature)
        {
            if ($merchant->isFeatureEnabled($feature))
            {
                (new Feature\Service)->deleteEntityFeature(
                    Feature\Type::ACCOUNTS, $merchant->getId(), $feature, ['should_sync' => true]
                );
                $isFeatureDisabled = true;
                $featuresDisabled = $feature;
            }
        }

        if ($isFeatureDisabled)
        {
            $payload = [
                'merchant_name' => $merchant->getName(),
                'email' => $merchant->getEmail(),
                'org_id' => $merchant->getOrgId(),
            ];

            $mail = new EsDisabledNotify($payload);
            Mail::send($mail);

            $this->trace->info(TraceCode::ES_FEATURE_FLAG_DISABLED, [
                'merchant_id' => $merchantId,
                'features_disabled' => $featuresDisabled,
            ]);
        }
    }
}
