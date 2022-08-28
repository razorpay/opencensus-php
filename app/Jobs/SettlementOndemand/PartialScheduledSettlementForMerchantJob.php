<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Settlement\Ondemand;
use Razorpay\Trace\Logger as Trace;

class PartialScheduledSettlementForMerchantJob extends Job
{
    const MAX_ATTEMPTS = 3;

    protected $mode;

    protected $merchantId;

    public function __construct($mode, $merchantId)
    {
        parent::__construct($mode);

        $this->mode = $mode;
        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_FOR_MERCHANT_JOB, [
            'merchant_id'   => $this->merchantId,
        ]);

        try
        {
            $input = [
                'settle_full_balance' => true
            ];

            $requestDetails = [
                'merchant_id'     => $this->merchantId,
                'scheduled'       => true,
                'mode'            => $this->mode,
            ];

            $response = (new Ondemand\Service)->create($input, $requestDetails);

            $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_FOR_MERCHANT_RESPONSE, [
                "merchant_id"   => $this->merchantId,
                "response"      => $response
            ]);

            $this->delete();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_PARTIAL_SCHEDULED_FOR_MERCHANT_JOB_ERROR,
                ["merchant_id" => $this->merchantId]
            );

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
