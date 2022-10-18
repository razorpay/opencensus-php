<?php

namespace RZP\Jobs\SettlementOndemand;

use App;

use RZP\Jobs\Job;
use RZP\Error\Error;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\RazorpayXClient;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand;
use RZP\Exception\BadRequestException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Settlement\OndemandFundAccount;

class RequestOndemandPayout extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 10;

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

            $this->app = App::getFacadeRoot();
            $this->repo = $this->app['repo'];


            $this->repo->transaction(function()
            {
                $this->settlementOndemandPayout = (new OndemandPayout\Repository)->findByIdAndMerchantIdWithLock(
                                                        $this->settlementOndemandPayoutId,
                                                        $this->merchantId);

                [$payoutStatus, $payoutId, $response] = (new OndemandPayout\Service)
                    ->makePayoutRequest($this->settlementOndemandPayoutId, $this->currency);

                (new OndemandPayout\Service)->updateStatusAfterPayoutRequest($payoutStatus,
                                                                             $payoutId,
                                                                             $this->settlementOndemandPayout,
                                                                             $response);
            });
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_PAYOUT_REQUEST_FAILURE,
                [
                    'merchant'                      => $this->merchantId,
                    'settlement_ondemand_payout_id' => $this->settlementOndemandPayoutId,
                ]);

            if ($this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->release(10 * $this->attempts() + random_int(0, 10));
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
