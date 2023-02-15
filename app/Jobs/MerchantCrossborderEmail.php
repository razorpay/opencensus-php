<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;
use Mail;
use RZP\Constants\Mode;
use RZP\Models\Transaction\Type;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Mail\Merchant as MerchantEmail;

class MerchantCrossborderEmail extends Job
{
    const MODE = 'mode';

    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 300;

    const DEFAULT_BUSINESS_NAME = 'Team';

    const DEFAULT_MONTH_YEAR = 'last';

    const DEFAULT_PREV_DAYS = 15;

    const UPLOADED = 'uploaded';
    const CREATED = 'created';

    const FIRS_AVAILABLE_NOTIFICATION = 'FIRS_AVAILABLE_NOTIFICATION';
    const OPGSP_IMPORT_INVOICE_REMINDER = 'OPGSP_IMPORT_INVOICE_REMINDER';

    /**
     * @var string
     */
    protected $queueConfigKey = 'cross_border_merchant_email';

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

    public $timeout = 300;

    public function __construct(array $payload)
    {
        $this->setMode($payload);

        parent::__construct($this->mode);

        $this->payload = $payload;
    }

    public function handle()
    {
        try {
            parent::handle();

            $this->trace->info(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_INIT,[
                'payload'  => $this->payload,
            ]);

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];

            $action     = $this->payload['action'];

            switch($action)
            {
                case self::OPGSP_IMPORT_INVOICE_REMINDER :
                    $this->sendInvoiceReminderEmailForOpgspImport();
                    break;
                case self::FIRS_AVAILABLE_NOTIFICATION:
                    $this->sendFIRSAvailableToDownloadEmail();
                    break;
                default:
                    $this->trace->info(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_INVALID_ACTION,[
                        'payload' => $this->payload,
                    ]);
            }

            $this->trace->info(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_COMPLETED,[
                'payload' => $this->payload,
            ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_FAILED,[
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

            $this->trace->info(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_RELEASED, [
                'payload'               => $this->payload,
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_DELETED, [
                'payload'           => $this->payload,
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);
        }
    }

    /**
     * Set mode for job.
     * @param $payload array
     *
     * @return void
     */
    protected function setMode(array $payload)
    {
        if (array_key_exists(self::MODE, $payload) === true) {
            $this->mode = $payload[self::MODE];
        }
        else {
            $this->mode = Mode::LIVE;
        }
    }

    protected function sendInvoiceReminderEmailForOpgspImport()
    {
        $merchantId = $this->payload['merchant_id'];

        // fetch merchant detail entity to get business name and contact email
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        $contactEmail = $merchantDetail->getContactEmail();
        if (!isset($contactEmail) or empty($contactEmail))
        {
            $this->delete();

            $this->trace->info(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_DELETED, [
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
                $this->checkRetry();
            }
            else
            {
                $this->delete();

                $this->trace->info(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_DELETED, [
                    'payload'           => $this->payload,
                    'message'           => 'Deleting the job as FIRS was not successfully uploaded.'
                ]);
            }
            return;
        }

        // fetch merchant detail entity to get business name and contact email
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

        $contactEmail = $merchantDetail->getContactEmail();
        if (!isset($contactEmail) or empty($contactEmail))
        {
            $this->delete();

            $this->trace->info(TraceCode::CROSS_BORDER_MERCHANT_EMAIL_JOB_DELETED, [
                'payload'           => $this->payload,
                'message'           => 'Deleting the job as contact email address is not present.'
            ]);
            return;
        }

        $businessName = $merchantDetail->getBusinessName();
        $documentDate = $document->getDocumentDate();

        $mailPayload = [
            'business_name' => (isset($businessName) and !empty($businessName)) ? $businessName : self::DEFAULT_BUSINESS_NAME,
            "contact_email" => $contactEmail,
            'firs_month_year' => (isset($documentDate) and !empty($documentDate)) ? date("M Y", $documentDate) : self::DEFAULT_MONTH_YEAR,
        ];

        $mail = new MerchantEmail\FirsAvailableMail($mailPayload);
        Mail::Send($mail);
    }
}
