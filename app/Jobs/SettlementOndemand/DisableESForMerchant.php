<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\EarlySettlementFeaturePeriod as featurePeriod;

class DisableESForMerchant extends Job
{
    protected $mode;

    protected $merchantId;

    const MAX_ATTEMPTS = 3;

    public function __construct($mode, $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::DISABLE_ES_FOR_MERCHANT, [
            'merchant_id'   => $this->merchantId,
        ]);

        try
        {
            (new featurePeriod\Service)->disableFeature($this->merchantId);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DISABLE_ES_FOR_MERCHANT_ERROR,
                [
                    'merchant_id'    => $this->merchantId,
                ]);

            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $this->delete();
            }
        }
    }

}
