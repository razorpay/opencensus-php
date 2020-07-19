<?php

namespace RZP\Jobs\SettlementOndemand;

use App;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\OndemandPayout;

class CreateSettlementOndemandPayoutReversal extends Job
{
    protected $mode;

    protected $settlementOndemandPayoutId;

    protected $merchantId;

    protected $reversalReason;

    const MAX_ATTEMPTS = 3;

    public function __construct($mode, $settlementOndemandPayoutId, $merchantId, $reversalReason)
    {
        parent::__construct($mode);

        $this->settlementOndemandPayoutId = $settlementOndemandPayoutId;

        $this->merchantId = $merchantId;

        $this->reversalReason = $reversalReason;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PAYOUT_REVERSAL_JOB, [
                'settlement_ondemand_payout_id'   => $this->settlementOndemandPayoutId,
                'reversal_reason'                 => $this->reversalReason,
            ]);

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];

            $this->repo->transaction(function()
            {
                (new Ondemand\Service)->createPartialReversal($this->settlementOndemandPayoutId, $this->merchantId ,$this->reversalReason);
            });
        }
        catch(\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_PAYOUT_REVERSAL_FAILURE,
                [
                    'settlement_ondemand_payout_id'   => $this->settlementOndemandPayoutId,
                    'reversal_reason'                 => $this->reversalReason,
                ]
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
