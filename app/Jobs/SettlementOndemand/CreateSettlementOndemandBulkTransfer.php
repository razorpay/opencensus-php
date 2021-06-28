<?php

namespace RZP\Jobs\SettlementOndemand;

use App;

use RZP\Jobs\Job;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\Ondemand\Attempt;
use RZP\Models\Settlement\OndemandPayout\Status;

class CreateSettlementOndemandBulkTransfer extends Job
{
    const PAYOUT_CREATION_FAILURE_RETRY_LIMIT = 10;

    const PAYOUT_REVERSAL_RETRY_LIMIT = 10;

    const DEFAULT_FAILURE_REASON = 'RequestOndemandBulkPayout job failure';

    protected $settlementOndemandAttemptId;

    protected $settlementOndemandAttempt;

    protected $settlementOndemandTransfer;

    protected $mode;

    public function __construct(string $mode , $settlementOndemandAttemptId, $settlementOndemandTransfer)
    {
        parent::__construct($mode);

        $this->settlementOndemandAttemptId = $settlementOndemandAttemptId;

        $this->settlementOndemandTransfer = $settlementOndemandTransfer;

        $this->mode = $mode;

    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_BULK_REQUEST, [
                'settlement_ondemand_attempt_id'   => $this->settlementOndemandAttemptId,
                'attempt number'                   => $this->attempts(),
            ]);

            $this->app = App::getFacadeRoot();

            $this->repo = $this->app['repo'];

            $this->repo->transaction(function()
            {
                $this->mutex->acquireAndRelease(
                    $this->settlementOndemandAttemptId,
                    function()
                    {
                        $this->settlementOndemandAttempt = (new Attempt\Repository)
                            ->findOrFail($this->settlementOndemandAttemptId);

                        [$payoutStatus, $payoutId, $response] = (new Attempt\Service)
                            ->makeBulkPayoutRequest(
                                $this->settlementOndemandAttemptId,
                                'INR',
                                $this->settlementOndemandTransfer);

                        (new Attempt\Service)->updateStatusAfterPayoutRequest(
                            $payoutStatus,
                            $payoutId,
                            $this->settlementOndemandAttempt,
                            $response,
                            $response['failure_reason']);
                    });
            });

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_ONDEMAND_BULK_REQUEST_FAILURE,
                [
                    'settlement_ondemand_attempt_id' => $this->settlementOndemandAttemptId,
                ]);

            if ($e->getCode() === ErrorCode::SERVER_ERROR_RAZORPAYX_PAYOUT_REVERSAL)
            {
                (new Attempt\Service)->updateStatusAfterPayoutRequest(
                        Status::REVERSED,
                        $e->getData()['response']['id'],
                        $this->settlementOndemandAttempt,
                        $e->getData()['response'],
                        $e->getData()['response']['failure_reason']);
            }
            else
            {
                if($this->attempts() < self::PAYOUT_CREATION_FAILURE_RETRY_LIMIT)
                {
                    $this->release(10 * $this->attempts() + random_int(0, 10));
                }
                else
                {
                    $failureReason = $e->getMessage() ?? self::DEFAULT_FAILURE_REASON;

                    $payoutId = $this->settlementOndemandTransfer->getPayoutId() ?? null;

                    (new Attempt\Service)->updateStatusAfterPayoutRequest(
                    Status::REVERSED,
                    $payoutId,
                    $this->settlementOndemandAttempt,
                    null,
                    $failureReason);

                    $this->delete();
                }
            }
       }
    }
}
