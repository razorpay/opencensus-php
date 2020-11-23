<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\OndemandFundAccount;

class CreateSettlementOndemandFundAccount extends Job
{
    protected $mode;

    protected $merchantId;

    const MAX_ATTEMPTS = 400;

    public function __construct($mode, $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_FUND_ACCOUNT_CREATE_JOB, [
            'merchant_id'   => $this->merchantId,
        ]);

        try
        {
            (new OndemandFundAccount\Service)->addOndemandFundAccountForMerchant($this->merchantId);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_FUND_ACCOUNT_CREATION_ERROR,
                [
                    'merchant_id'    => $this->merchantId,
                ]);

            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(random_int(1, 10 * $this->attempts()));
            }
            else
            {
                $this->delete();
            }
        }
    }
}
