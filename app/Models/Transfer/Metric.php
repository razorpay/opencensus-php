<?php

namespace RZP\Models\Transfer;

use App;
use RZP\Models\Base;

class Metric extends Base\Core
{
    use Base\Traits\MetricTrait;

    //Metric Names
    const TRANSFER_CREATE_FAILED                   = 'transfer_create_failed';
    const TRANSFER_CREATE_SUCCESS                  = 'transfer_create_success';
    const TRANSFER_REVERSAL_SUCCESS                = 'transfer_reversal_success';
    const TRANSFER_REVERSAL_FAILED                 = 'transfer_reversal_failed';
    const TRANSFER_PROCESS_SUCCESS                 = 'transfer_process_success';
    const TRANSFER_PROCESS_FAILED                  = 'transfer_process_failed';
    const TRANSFER_ROUTE                           = 'transfer_route';
    const TRANSFER_TO_TYPE                         = 'transfer_to_type';
    const TRANSFER_SOURCE                          = 'transfer_source';
    const TRANSFER_PROCESSING_TIME                 = 'transfer_processing_time';
    const BATCH_TRANSFER_PROCESSING_TIME           = 'batch_transfer_processing_time';
    const TRANSFER_PROCESSING_TIME_FOR_CF_AND_SL   = 'transfer_processing_time_for_cf_and_sl';
    const TRANSFER_PROCESSING_TIME_IN_WORKER       = 'transfer_processing_time_in_worker';
    const TRANSFERS_PROCESSING_TIME_IN_SYNC        = 'transfer_processing_time_in_sync';
    const SOURCE_ID_PROCESSING_TIME_IN_WORKER      = 'source_id_processing_time_in_worker';
    const SEMAPHORE_ACQUIRE_SUCCESS                = 'semaphore_acquire_success';
    const SEMAPHORE_ACQUIRE_TIME_TAKEN             = 'semaphore_acquire_time_taken';
    const SEMAPHORE_ACQUIRE_FAILURE                = 'semaphore_acquire_failure';

    public function pushCreateSuccessMetrics(array $input = [])
    {
        $dimensions = $this->getCreateDefaultDimensions($input);

        $this->trace->count(self::TRANSFER_CREATE_SUCCESS, $dimensions);
    }

    public function pushCreateFailedMetrics(\Throwable $e)
    {
        $this->pushExceptionMetrics($e, self::TRANSFER_CREATE_FAILED, $this->getCreateDefaultDimensions());
    }

    public function pushReversalSuccessMetrics()
    {
        $dimensions = [
            self::TRANSFER_ROUTE => $this->getRouteName(),
        ];
        $this->trace->count(self::TRANSFER_REVERSAL_SUCCESS, $dimensions);
    }

    public function pushReversalFailedMetrics(\Throwable $e)
    {
        $dimensions = [
            self::TRANSFER_ROUTE => $this->getRouteName(),
        ];
        $this->pushExceptionMetrics($e, self::TRANSFER_REVERSAL_FAILED, $dimensions);
    }

    public function pushTransferProcessSuccessMetrics()
    {
        $this->trace->count(self::TRANSFER_PROCESS_SUCCESS, $this->getCreateDefaultDimensions());
    }

    public function pushTransferProcessFailedMetrics(\Throwable $e)
    {
        $this->pushExceptionMetrics($e, self::TRANSFER_PROCESS_FAILED, $this->getCreateDefaultDimensions());
    }

    public function pushTransferProcessingTimeMetrics($sourceType, $processingTime)
    {
        $dimensions = [
            self::TRANSFER_ROUTE  => $this->getRouteName(),
            self::TRANSFER_SOURCE => $sourceType,
        ];

        $this->trace->histogram(self::TRANSFER_PROCESSING_TIME, $processingTime, $dimensions);
    }

    public function pushTransferProcessingBatchTimeMetrics($sourceType, $processingTime)
    {
        $dimensions = [
            self::TRANSFER_SOURCE => $sourceType,
        ];

        $this->trace->histogram(self::BATCH_TRANSFER_PROCESSING_TIME, $processingTime, $dimensions);
    }

    public function pushTransferProcessingTimeMetricsForCfAndSl($sourceType, $processingTime)
    {
        $dimensions = [
            self::TRANSFER_ROUTE  => $this->getRouteName(),
            self::TRANSFER_SOURCE => $sourceType,
        ];

        $this->trace->histogram(self::TRANSFER_PROCESSING_TIME_FOR_CF_AND_SL, $processingTime, $dimensions);
    }

    public function pushTransferProcessingTimeInWorkerMetrics($sourceType, $processingTime)
    {
        $dimensions = [
            self::TRANSFER_ROUTE  => $this->getRouteName(),
            self::TRANSFER_SOURCE => $sourceType,
        ];

        $this->trace->histogram(self::TRANSFER_PROCESSING_TIME_IN_WORKER, $processingTime, $dimensions);
    }

    public function pushSourceIdProcessingTimeInWorkerMetrics($sourceType, $processingTime)
    {
        $dimensions = [
            self::TRANSFER_ROUTE   => $this->getRouteName(),
            self::TRANSFER_SOURCE  => $sourceType,
        ];

        $this->trace->histogram(self::SOURCE_ID_PROCESSING_TIME_IN_WORKER, $processingTime, $dimensions);
    }

    public function pushTransfersProcessingTimeInSyncMetrics($processingTime)
    {
        $this->trace->histogram(self::TRANSFERS_PROCESSING_TIME_IN_SYNC, $processingTime);
    }

    public function pushSemaphoreAcquireSuccessMetrics($timeTakenToAcquireMs)
    {
        $this->trace->count(self::SEMAPHORE_ACQUIRE_SUCCESS);

        $this->trace->histogram(self::SEMAPHORE_ACQUIRE_TIME_TAKEN, $timeTakenToAcquireMs);
    }

    public function pushSemaphoreAcquireFailureMetrics()
    {
        $this->trace->count(self::SEMAPHORE_ACQUIRE_FAILURE);
    }

    private function getCreateDefaultDimensions(array $input = [])
    {
        return $dimensions = [
            self::TRANSFER_ROUTE   => $this->getRouteName(),
            self::TRANSFER_TO_TYPE => isset($input[ToType::ACCOUNT]) ? ToType::ACCOUNT : ToType::CUSTOMER
        ];
    }

    private function getRouteName()
    {
        if ($this->app->runningInQueue() === true)
        {
            $workerName = $this->app['worker.ctx']?->getJobName();

            return $workerName;
        }

        $routeName = $this->app['api.route']?->getCurrentRouteName();

        return $routeName;
    }
}
