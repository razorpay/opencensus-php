<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;
use RZP\Models\Payment;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Gateway\Downtime\Webhook\Constants\DowntimeService;
use RZP\Models\Payment\Downtime\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Downtime;

class PaymentDowntimeEvent extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 2; // Previously 5
    const RELEASE_WAIT_SECS = 10;

    /** {@inheritDoc} */
    protected $queueConfigKey = 'webhook_event';

    /** {@inheritDoc} */
    public $timeout = 900; // I.e. 15m ~= (5000 http calls to stork * 50ms) + (5000 DB calls) + (margin)

    /** @var string See \RZP\Models\Payment\Downtime\Status */
    public $status;

    /** @var string */
    public $serializedDowntime;

    public $lastSeverity;

    public function __construct(string $mode, $status, string $serializedDowntime, $lastSeverity=null)
    {
        parent::__construct($mode);

        $this->status = $status;
        $this->serializedDowntime = $serializedDowntime;
        $this->lastSeverity = $lastSeverity;
    }

    public function handle()
    {
        $timeStarted = millitime();
        $downtimeType = "UNKNOWN";

        parent::handle();

        try
        {
            /** @var \RZP\Models\Payment\Downtime\Entity */
            $downtime = unserialize($this->serializedDowntime); // nosemgrep : php.lang.security.unserialize-use.unserialize-use

            $downtimeType = ($downtime->getMerchantId() === null) ? DowntimeService::PLATFORM : DowntimeService::MERCHANT;

            $this->trace->info(
                TraceCode::PAYMENT_DOWNTIME_EVENT_JOB_RECEIVED,
                ['status' => $this->status, 'downtime' => $downtime->getId(), "merchantId" => $downtime->getMerchantId()]
            );

            /*
             * FPX is a payment method supported in Malaysia. Merchants in a country other than My are not supposed to
             * receive this downtime notification hence we need to suppress this notification temporarily to avoid the noise for those merchants
             * and redundant load on the razorpay infra (Stork Service).
             */
            if ($downtime->getMethod() === Payment\Method::FPX)
            {
                return ;
            }

            if(((bool) ConfigKey::get(ConfigKey::ENABLE_DOWNTIME_WEBHOOKS, false)) === true)
            {
                (new Downtime\Service())->{'eventDowntime' . ucfirst($this->status)}($downtime, $this->lastSeverity);
            }

            $this->trace->count(Metric::PAYMENT_DOWNTIME_EVENT_JOB_COUNT,
                ['downtime_type' => $downtimeType, 'downtime_status' => $this->status, 'status' => 'successful']
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            $this->trace->count(Metric::PAYMENT_DOWNTIME_EVENT_JOB_COUNT,
                ['downtime_type' => $downtimeType, 'downtime_status' => $this->status, 'status' => 'failed']
            );

            if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->trace->count(Metric::PAYMENT_DOWNTIME_EVENT_JOB_COUNT,
                    ['downtime_type' => $downtimeType, 'downtime_status' => $this->status, 'status' => 'retried']
                );

                $this->release(self::RELEASE_WAIT_SECS);
            }
        }

        $this->trace->histogram(Metric::PAYMENT_DOWNTIME_EVENT_JOB_DURATION,
            millitime() - $timeStarted,
            ['downtime_type' => $downtimeType]
        );
    }
}
