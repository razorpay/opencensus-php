<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Services\RazorpayXClient;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Settlement\OndemandFundAccount;

class RequestOndemandPayout extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const DEFAULT_FAILURE_REASON = 'RequestOndemandPayout job failure';

    protected $settlementOndemandPayoutId;

    protected $merchantId;

    /** @var Ondemand\Entity $settlementOndemandPayout */
    protected $settlementOndemandPayout;

    protected $currency;

    public function __construct(string $mode , $settlementOndemandPayoutId, $merchantId, $currency)
    {
        parent::__construct($mode);

        $this->settlementOndemandPayoutId = $settlementOndemandPayoutId;

        $this->merchantId = $merchantId;

        $this->currency = $currency;
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_PAYOUT_REQUEST, [
                'ondemand_payout_id'   => $this->settlementOndemandPayoutId,
            ]);

            $this->settlementOndemandPayout = (new OndemandPayout\Repository)->findByIdAndMerchantId(
                                                    $this->settlementOndemandPayoutId, $this->merchantId);

            [$payoutStatus, $payoutId, $response] = (new OndemandPayout\Service)
                                                    ->makePayoutRequest($this->settlementOndemandPayoutId, $this->currency);

            (new OndemandPayout\Service)->updateStatusAfterPayoutRequest($payoutStatus, $payoutId, $this->settlementOndemandPayout);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_PAYOUT_REQUEST_FAILURE,
                [
                    'merchant'                      => $this->settlementOndemandPayout->getMerchantId(),
                    'settlement_ondemand_payout_id' => $this->settlementOndemandPayout->getId(),
                ]);

            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(1);
            }
            else
            {
                $failureReason = $e->getMessage() ?? self::DEFAULT_FAILURE_REASON;

                (new OndemandPayout\Service)->initiateReversal($this->settlementOndemandPayoutId,
                                                                $this->merchantId,
                                                                $failureReason);

                $this->delete();
            }
        }
    }
}
