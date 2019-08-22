<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement\Bucket\Core;

class Bucket extends Job
{
    const MAX_ATTEMPTS = 5;

    /**
     * @var string
     */
     protected $queueConfigKey = 'settlement_bucket';

    /**
     * @var string
     */
     protected $merchantId;

    /**
     * @var mixed|null
     */
     protected $settledAt;

    /**
     * @param string $mode
     * @param string $merchantId
     * @param null   $settledAt
     */
    public function __construct(string $mode, string $merchantId, $settledAt)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->settledAt  = $settledAt;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        try
        {
            (new Core)->addMerchantToSettlementBucket($this->merchantId, $this->settledAt);
        }
        catch (\Throwable $e)
        {
            // if the max attempt is not exhausted then release the job for retry
            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(1);
            }

            $this->trace->error(
                TraceCode::FAILED_TO_ADD_MERCHANT_TO_SETTLEMENT_BUCKET,
                [
                    'merchant_id' => $this->merchantId,
                    'settled_at'  => $this->settledAt,
                    'attempt'     => $this->attempts(),
                ]);
        }
    }
}
