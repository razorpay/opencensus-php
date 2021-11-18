<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;
use RZP\Models\Admin\ConfigKey;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Downtime;

class PaymentDowntimeEvent extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS = 10;

    /** {@inheritDoc} */
    protected $queueConfigKey = 'webhook_event';

    /** {@inheritDoc} */
    public $timeout = 300; // I.e. 5m ~= 5000 http calls to stork * 50ms.

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
        parent::handle();

        try
        {
            /** @var \RZP\Models\Payment\Downtime\Entity */
            $downtime = unserialize($this->serializedDowntime);

            $this->trace->info(
                TraceCode::PAYMENT_DOWNTIME_EVENT_JOB_RECEIVED,
                ['status' => $this->status, 'downtime' => $downtime->getId(), "merchantId" => $downtime->getMerchantId()]
            );

            if(((bool) ConfigKey::get(ConfigKey::ENABLE_DOWNTIME_WEBHOOKS, false)) === true)
            {
                (new Downtime\Service())->{'eventDowntime' . ucfirst($this->status)}($downtime, $this->lastSeverity);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(self::RELEASE_WAIT_SECS);
            }
        }
    }
}
