<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\FeeRecovery\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FeeRecovery\Core as FeeRecoveryCore;

class FeeRecoveryRetry extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const DELAY = 300;

    // Overriding timeout with 300 for the time being, since we don't know how much time the process will take.
    public $timeout = 300;

    protected $trace;

    protected $feeRecoveryPayoutId;

    public function __construct(string $mode, string $feeRecoveryPayoutId)
    {
        parent::__construct($mode);

        $this->feeRecoveryPayoutId = $feeRecoveryPayoutId;
    }

    public function handle()
    {
        parent::handle();

        $data = [
            Entity::PREVIOUS_RECOVERY_PAYOUT_ID    => $this->feeRecoveryPayoutId
        ];

        $this->trace->info(
            TraceCode::FEE_RECOVERY_RETRY_CRON_PROCESS,
            $data);

        try
        {
            $payout = (new FeeRecoveryCore)->recreateFeeRecoveryPayout($this->feeRecoveryPayoutId);

            if ($payout !== null)
            {
                $this->trace->info(
                    TraceCode::FEE_RECOVERY_RETRY_CRON_SUCCESS,
                    [
                        'new_recovery_payout_id'                      => $payout->getPublicId(),
                        'new_recovery_payout_amount'                  => $payout->getAmount(),
                        Entity::PREVIOUS_RECOVERY_PAYOUT_ID           => $this->feeRecoveryPayoutId,
                    ]
                );
            }
            else
            {
                $this->trace->info(
                    TraceCode::FEE_RECOVERY_RETRY_CRON_RAN,
                    [
                        Entity::PREVIOUS_RECOVERY_PAYOUT_ID           => $this->feeRecoveryPayoutId,
                    ]
                );
            }

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_RETRY_CRON_FAILURE_DELETE_JOB,
                    $data);
            }
            else
            {
                $this->release(self::DELAY);

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_RETRY_CRON_FAILURE,
                    $data);
            }
        }
    }
}
