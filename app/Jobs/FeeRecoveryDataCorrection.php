<?php

namespace RZP\Jobs;

use App;

use RZP\Models\FeeRecovery\Metric;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FeeRecovery\Core as FeeRecoveryCore;
use RZP\Models\Merchant\Core as MerchantCore;

class FeeRecoveryDataCorrection extends Job
{
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

    // Overriding timeout with 600 for the time being, since we don't know how much time the process will take.
    public $timeout = 600;

    public function __construct(string $mode,
                                string $balanceId = null,
                                int $startTimeStamp = null,
                                int $endTimeStamp = null)
    {
        parent::__construct($mode);
        $this->balanceId = $balanceId;
        $this->startTimeStamp = $startTimeStamp;
        $this->endTimeStamp = $endTimeStamp;
    }

    public function handle()
    {
        $startTime = microtime(true);
        parent::handle();

        $isBalanceIdEnabledForFeeRecoveryDataCorrection = $this->isBalanceIdEligibleForFeeRecoveryDataCorrection();
        if (!$isBalanceIdEnabledForFeeRecoveryDataCorrection) {
            $this->delete();
            return false;
        }

        $this->trace->count(Metric::FEE_RECOVERY_DATA_CORRECTION_JOB, [Metric::STATUS => Metric::INITIATED]);
        try {

            $feeRecoveryCore = new FeeRecoveryCore();
            $data = [
                'balance_id' => $this->balanceId,
                'from' => $this->startTimeStamp,
                'to' => $this->endTimeStamp,
            ];
            $response = $feeRecoveryCore->processFeeRecoveryDataCorrection($data);
            $this->trace->info(
                TraceCode::FEE_RECOVERY_DATA_CORRECTION_JOB_SUCCESS,
                [
                    'response' => $response,
                    'balance_id' => $this->balanceId,
                ]
            );
            $this->trace->count(Metric::FEE_RECOVERY_DATA_CORRECTION_JOB, [Metric::STATUS => Metric::SUCCESS]);
            $endTime = microtime(true);

            // trace the time taken by the job
            $this->trace->info(
                TraceCode::FEE_RECOVERY_DATA_CORRECTION_JOB_TIME_TAKEN,
                [
                    'balance_id' => $this->balanceId,
                    'time_taken' => $endTime - $startTime,
                ]
            );

            $this->delete();
            return $response;
        } catch (\Throwable $exception) {
            $this->delete();
            $this->trace->count(Metric::FEE_RECOVERY_DATA_CORRECTION_JOB, [Metric::STATUS => Metric::FAILURE, Metric::CODE => $exception->getCode()]);
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FEE_RECOVERY_DATA_CORRECTION_JOB_EXCEPTION,
                $data);
            throw $exception;
        }
    }

    /**
     * @return bool
     */
    private function isBalanceIdEligibleForFeeRecoveryDataCorrection(): bool
    {
        $properties = [
            'id' => $this->balanceId,
            'experiment_id' => "fee_recovery_data_correction_enabled",
            'request_data' => json_encode(['id' => $this->balanceId])
        ];
        return (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');
    }
}
