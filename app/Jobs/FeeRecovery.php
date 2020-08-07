<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\FeeRecovery\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FeeRecovery\Core as FeeRecoveryCore;

class FeeRecovery extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const DELAY = 300;

    // Overriding timeout with 300 for the time being, since we don't know how much time the process will take.
    public $timeout = 300;

    protected $trace;

    /**
     * @var string|null
     */
    protected $balanceId;

    /**
     * @var int|null
     */
    protected $startTimeStamp;

    /**
     * @var int|null
     */
    protected $endTimeStamp;

    /**
     * @var string|null
     */
    private $feeRecoveryPayoutId;

    public function __construct(string $mode,
                                string $feeRecoveryPayoutId = null,
                                string $balanceId = null,
                                int $startTimeStamp = null,
                                int $endTimeStamp = null)
    {
        parent::__construct($mode);

        $this->feeRecoveryPayoutId = $feeRecoveryPayoutId;

        $this->balanceId = $balanceId;

        $this->startTimeStamp = $startTimeStamp;

        $this->endTimeStamp = $endTimeStamp;
    }

    public function handle()
    {
        parent::handle();

        if ($this->feeRecoveryPayoutId !== null)
        {
            $this->feeRecoveryRetryHandle();
        }
        else
        {
            $this->feeRecoveryHandle();
        }

    }

    protected function feeRecoveryHandle()
    {
        $data = [
            'balance_id'    => $this->balanceId,
            'from'          => $this->startTimeStamp,
            'to'            => $this->endTimeStamp,
        ];

        $this->trace->info(
            TraceCode::FEE_RECOVERY_CRON_PROCESS,
            $data);

        try
        {
            $payout = (new FeeRecoveryCore)->createFeeRecoveryPayout($data);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_CRON_SUCCESS,
                [
                    'recovery_payout'   => $payout->toArrayPublic(),
                    'balance_id'        => $this->balanceId,
                ]
            );

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
                    TraceCode::FEE_RECOVERY_CRON_FAILURE_DELETE_JOB,
                    $data);

                $operation = 'Fee Recovery job failed thrice';

                (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_ca_rbl_alerts');
            }
            else
            {
                $this->release(self::DELAY);

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_CRON_FAILURE,
                    $data);
            }
        }
    }

    protected function feeRecoveryRetryHandle()
    {
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
