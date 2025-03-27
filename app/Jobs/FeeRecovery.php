<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;

use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\FeeRecovery\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Schedule\Task\Entity as TaskEntity;
use RZP\Models\FeeRecovery\Core as FeeRecoveryCore;
use RZP\Models\FeeRecovery\Metric as FeeRecoveryMetrics;

class FeeRecovery extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 3;

    const DELAY = 300;

    // Overriding timeout with 900 for the time being, since we don't know how much time the process will take.
    public $timeout = 900;

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

    /**
     * @var TaskEntity|null
     */
    private $task;

    private $WHITELISTED_ERROR_CODES_FOR_DATA_CORRECTION = [
        ErrorCode::BAD_REQUEST_FEE_RECOVERY_ALREADY_INITIATED,
        ErrorCode::BAD_REQUEST_LOGIC_ERROR_FEE_RECOVERY_ENTITY_MISSING
    ];

    public function __construct(string $mode,
                                string $feeRecoveryPayoutId = null,
                                string $balanceId = null,
                                int $startTimeStamp = null,
                                int $endTimeStamp = null,
                                TaskEntity $task = null)
    {
        parent::__construct($mode);

        $this->feeRecoveryPayoutId = $feeRecoveryPayoutId;

        $this->balanceId = $balanceId;

        $this->startTimeStamp = $startTimeStamp;

        $this->endTimeStamp = $endTimeStamp;

        $this->task = $task;
    }

    public function handle()
    {
        $startTime = microtime(true);
        parent::handle();

        if ($this->feeRecoveryPayoutId !== null)
        {
            $this->feeRecoveryRetryHandle();
        }
        else
        {
            $this->feeRecoveryHandle();
        }

        $endTime = microtime(true);
        $this->trace->info(
            TraceCode::FEE_RECOVERY_JOB_TIME_TAKEN,
            [
                'balance_id' => $this->balanceId,
                'time_taken' => $endTime - $startTime,
            ]
        );
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
            $this->trace->count(FeeRecoveryMetrics::FEE_RECOVERY_CRON_JOB, [FeeRecoveryMetrics::STATUS => FeeRecoveryMetrics::INITIATED]);

            $feeRecoveryCore = new FeeRecoveryCore();

            $response = $feeRecoveryCore->createFeeRecoveryPayout($data);

            $this->trace->info(
                TraceCode::FEE_RECOVERY_CRON_SUCCESS,
                [
                    'response'   => $response,
                    'balance_id' => $this->balanceId,
                ]
            );

            $feeRecoveryCore->updateNextRunAndLastRunForFeeRecoveryTasks($this->task);

            $this->repoManager->saveOrFail($this->task);

            $this->trace->count(FeeRecoveryMetrics::FEE_RECOVERY_CRON_JOB, [FeeRecoveryMetrics::STATUS => FeeRecoveryMetrics::SUCCESS]);

            $this->delete();
        }
        catch (\Throwable $ex)
        {
            $this->trace->count(FeeRecoveryMetrics::FEE_RECOVERY_CRON_JOB, [
                FeeRecoveryMetrics::STATUS => FeeRecoveryMetrics::FAILURE,
                FeeRecoveryMetrics::CODE => $ex->getCode()
            ]);

            if ($ex->getCode() === ErrorCode::BAD_REQUEST_LOGIC_ERROR_FEE_RECOVERY_ENTITY_MISSING) {

                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::FEE_RECOVERY_CRON_FAILURE_DELETE_JOB,
                    $data);

                FeeRecoveryDataCorrection::dispatch($this->mode, $this->balanceId, $this->startTimeStamp, $this->endTimeStamp);
                $this->delete();

                return;
            }

            if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::FEE_RECOVERY_CRON_FAILURE_DELETE_JOB,
                    $data);
                if(in_array($ex->getCode(), $this->WHITELISTED_ERROR_CODES_FOR_DATA_CORRECTION)) {
                    FeeRecoveryDataCorrection::dispatch($this->mode, $this->balanceId, $this->startTimeStamp, $this->endTimeStamp);
                }
            }
            else
            {
                if ($ex->getCode() === ErrorCode::BAD_REQUEST_FEE_RECOVERY_AMOUNT_INSUFFICIENT)
                {
                    $feeRecoveryCore->updateNextRunAtForNegativeFees($this->task, $this->balanceId);
                }

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
