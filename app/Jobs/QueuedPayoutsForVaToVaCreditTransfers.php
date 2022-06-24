<?php


namespace RZP\Jobs;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class QueuedPayoutsForVaToVaCreditTransfers extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;

    // the delay is in seconds
    // used in an exponential backoff manner
    const MIN_RETRY_DELAY = 3;

    /**
     * @var string
     */
    protected $queueConfigKey = 'queued_payouts_for_va_to_va_credit_transfers';

    protected $trace;

    protected $payoutId;

    public function __construct(string $mode, string $payoutId)
    {
        parent::__construct($mode);

        $this->payoutId = $payoutId;
    }

    public function handle()
    {
        parent::handle();

        $traceData = [ 'payout_id' => $this->payoutId ];

        $this->trace->info(
            TraceCode::PAYOUT_VA_TO_VA_QUEUE_REQUEST,
            $traceData + [
                'attempt' => $this->attempts(),
            ]);

        try
        {
            $payout = (new Payout\Core)->handleTransferForQueuedVaToVaPayout($this->payoutId);

            $this->trace->info(
                TraceCode::PAYOUT_VA_TO_VA_QUEUE_SUCCESS,
                $traceData + [
                    'payout_status' => $payout->getStatus(),
                ]);

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYOUT_VA_TO_VA_QUEUE_ATTEMPT_FAILED,
                $traceData + [
                    'attempt' => $this->attempts(),
                ]);

            if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->trace->info(
                    TraceCode::PAYOUT_VA_TO_VA_QUEUE_FAILURE_EXCEPTION,
                    $traceData);

                try
                {
                    $payout = (new Payout\Core)->handleReversalForFailedVaToVaPayout($this->payoutId);

                    $this->trace->info(
                        TraceCode::PAYOUT_VA_TO_VA_REVERSED,
                        $traceData + [
                            'payout_status' => $payout->getStatus(),
                        ]);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->info(
                        TraceCode::PAYOUT_VA_TO_VA_REVERSAL_FAILED,
                        $traceData);
                }

                $this->delete();
            }
            else
            {
                $noOfAttempts = $this->attempts();

                $this->release(self::MIN_RETRY_DELAY * pow(2, ($noOfAttempts - 1)));
            }
        }
    }
}
