<?php


namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Core as MerchantCore;


class SkipOnboardingCommFromHubSpot extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 1;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    public $timeout = 1000;

    private $partnerId;

    private $partnerEmail;

    private $subMerchantEmails;


    public function __construct(string $mode, string $partnerId, string $partnerEmail, array $subMerchantEmails)
    {
        parent::__construct($mode);

        $this->partnerId = $partnerId;

        $this->partnerEmail = $partnerEmail;

        $this->subMerchantEmails = $subMerchantEmails;
    }

    public function handle()
    {
        parent::handle();

        try {
            $this->trace->info(
                TraceCode::SKIP_HUBSPOT_ONBOARDING_COMM_REQUEST,
                [
                    'mode'               => $this->mode,
                    'partner_id'         => $this->partnerId,
                    'submerchant_emails' => $this->subMerchantEmails,
                ]
            );

            $merchantCore = new MerchantCore();

            foreach ($this->subMerchantEmails as $subMerchantEmail)
            {
                if ($this->partnerEmail !== $subMerchantEmail)
                {
                    $merchantCore->skipMerchantOnboardingCommFromHubSpot($subMerchantEmail);
                }
            }

            $this->delete();
        }
        catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SKIP_HUBSPOT_ONBOARDING_COMM_REQUEST_ERROR,
                [
                    'mode'         => $this->mode,
                    'partner_id'   => $this->partnerId,
                    'merchant_ids' => $this->subMerchantEmails,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT) {
            $this->trace->error(TraceCode::SKIP_HUBSPOT_ONBOARDING_COMM_QUEUE_DELETE, [
                'merchant_ids' => $this->subMerchantEmails,
                'job_attempts' => $this->attempts(),
                'message' => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        } else {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
