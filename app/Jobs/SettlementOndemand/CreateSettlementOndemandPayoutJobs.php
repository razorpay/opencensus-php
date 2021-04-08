<?php

namespace RZP\Jobs\SettlementOndemand;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\OndemandPayout;

class CreateSettlementOndemandPayoutJobs extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const RETRY_DELAY = 60;

    /** @var Ondemand\Entity $settlementOndemand */
    protected $settlementOndemand;

    protected $merchantId;

    protected $settlementOndemandId;

    protected $mode;

    public function __construct(string $mode , $settlementOndemandId, $merchantId)
    {
        parent::__construct($mode);

        $this->settlementOndemandId = $settlementOndemandId;

        $this->merchantId = $merchantId;

        $this->mode = $mode;
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->settlementOndemand = (new Ondemand\Repository)->findOrFail($this->settlementOndemandId);

            $this->trace->info(TraceCode::MAKE_SETTLEMENT_ONDEMAND_PAYOUT_REQUEST, [
                'ondemand_id'   => $this->settlementOndemand->getId(),
                'merchant_id'   => $this->settlementOndemand->getMerchantId(),
            ]);

            $settlementOndemandPayoutIds = (new OndemandPayout\Repository)
                                ->fetchIdsByOndemandIdAndMerchantId($this->settlementOndemand->getId(), $this->settlementOndemand->getMerchantId());

            foreach($settlementOndemandPayoutIds as $settlementOndemandPayoutId)
            {
                RequestOndemandPayout::dispatch($this->mode,
                                                $settlementOndemandPayoutId,
                                                $this->merchantId,
                                                $this->settlementOndemand->getCurrency());
            }
        }
        catch (\Throwable $e)
        {
            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(self::RETRY_DELAY);

                return ;
            }
            else
            {
                $this->delete();
            }

            try
            {
                (new Ondemand\Service)->createSettlementOndemandReversal($this->settlementOndemandId, $this->merchantId, 'job failure');
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::REVERSAL_FAILURE_FOR_SETTLEMENT_JOB_FAILURE,
                    [
                        'merchant_id'      => $this->merchantId,
                        'ondemand_id'      => $this->settlementOndemandId,
                    ]);
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAKE_SETTLEMENT_ONDEMAND_PAYOUT_REQUEST_FAILURE,
                [
                    'merchant_id'      => $this->merchantId,
                    'ondemand_id'      => $this->settlementOndemandId,
                ]);
        }
    }
}
