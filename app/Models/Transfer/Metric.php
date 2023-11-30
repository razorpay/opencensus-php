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
    const MERCHANT_CATEGORY                        = 'merchant_category';
    const MERCHANT_PLATFORM_FEE_FETCH_REQUEST      = 'merchant_platform_fee_fetch_request';
    const MERCHANT_PLATFORM_FEE_FETCH_FAILURE      = 'merchant_platform_fee_fetch_failure';
    const MERCHANT_PLATFORM_FEE_FETCH_TIME_IN_MS   = 'merchant_platform_fee_fetch_time_in_ms';
    const ASYNC_BALANCE_UPDATE_FOR_TRANSFER_TXN_FAILED  = 'async_balance_update_for_transfer_txn_failed';
    const ASYNC_BALANCE_UPDATE_FOR_TRANSFER_TXN_SUCCESS = 'async_balance_update_for_transfer_txn_success';
    const ASYNC_BALANCE_UPDATE_FOR_TRANSFER_TXN_TIME    = 'async_balance_update_for_transfer_txn_time';
    const TRANSFER_NOT_FOUND                            = 'transfer_not_found';
    const TXN_NOT_FOUND                                 = 'txn_not_found';
    const OTHER_ERROR                                   = 'other_error';
    const IS_SYNC_PROCESSING_ENABLED                    = 'is_sync_processing_enabled';
    const PAYMENT_TRANSFERS_CREATE_LATENCY              = 'payment_transfers_create_latency';
    const PG_LEDGER_TRANSFER_TRANSACTION_CREATION_DELAY = 'pg_ledger_transfer_transaction_creation_delay';
    const PENDING_PAYMENT_TRANSFERS_COUNT               = 'pending_payment_transfers_count';
    const PENDING_ORDER_TRANSFERS_COUNT                 = 'pending_order_transfers_count';
    const TRANSFER_WEBHOOK_DISPATCH_FAILURE             = 'transfer_webhook_dispatch_failure';
    const TRANSFER_TRANSACTION_CREATE_FAILED            = 'transfer_transaction_create_failed';

    public function pushCreateSuccessMetrics(array $input = [])
    {
        $dimensions = $this->getCreateDefaultDimensions($input);

        $this->trace->count(self::TRANSFER_CREATE_SUCCESS, $dimensions);
    }

    public function pushCreateFailedMetrics(\Throwable $e, $input = [])
    {
        $this->pushExceptionMetrics($e, self::TRANSFER_CREATE_FAILED, $this->getCreateDefaultDimensions($input));
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

    public function pushTransferProcessingTimeMetrics($sourceType, $processingTime, $category, $isSyncProcessingEnabled)
    {
        $dimensions = [
            self::TRANSFER_ROUTE              => $this->getRouteName(),
            self::TRANSFER_SOURCE             => $sourceType,
            self::MERCHANT_CATEGORY           => $category,
            self::IS_SYNC_PROCESSING_ENABLED  => $isSyncProcessingEnabled,
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

    public function pushTransferProcessingTimeMetricsForCfAndSl($sourceType, $processingTime, $isSyncProcessingEnabled)
    {
        $dimensions = [
            self::TRANSFER_ROUTE             => $this->getRouteName(),
            self::TRANSFER_SOURCE            => $sourceType,
            self::IS_SYNC_PROCESSING_ENABLED => $isSyncProcessingEnabled,
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

    public function pushTransfersProcessingTimeInSyncMetrics($processingTime, $category)
    {
        $dimensions = [
            self::MERCHANT_CATEGORY  => $category,
        ];

        $this->trace->histogram(self::TRANSFERS_PROCESSING_TIME_IN_SYNC, $processingTime, $dimensions);
    }

    public function pushAsyncBalanceUpdateForTransferTxnFailedMetrics($transferNotFound, $txnNotFound, $otherError)
    {
        $dimensions = [
            self::TRANSFER_NOT_FOUND  => $transferNotFound,
            self::TXN_NOT_FOUND       => $txnNotFound,
            self::OTHER_ERROR         => $otherError,
        ];

        $this->trace->count(self::ASYNC_BALANCE_UPDATE_FOR_TRANSFER_TXN_FAILED, $dimensions);
    }

    public function pushAsyncBalanceUpdateForTransferTxnSuccessMetrics($startTime)
    {
        $processingTime = get_diff_in_millisecond($startTime);

        $this->trace->count(self::ASYNC_BALANCE_UPDATE_FOR_TRANSFER_TXN_SUCCESS);

        $this->trace->histogram(self::ASYNC_BALANCE_UPDATE_FOR_TRANSFER_TXN_TIME, $processingTime);
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

    public function pushPaymentTransfersCreateLatencyMetrics($startTime, $isSyncProcessingEnabled)
    {
        $latency = get_diff_in_millisecond($startTime);

        $dimensions = [
            self::IS_SYNC_PROCESSING_ENABLED => $isSyncProcessingEnabled
        ];

        $this->trace->histogram(self::PAYMENT_TRANSFERS_CREATE_LATENCY, $latency, $dimensions);
    }

    private function getCreateDefaultDimensions(array $input = [])
    {
        return $dimensions = [
            self::TRANSFER_ROUTE   => $this->getRouteName(),
            self::TRANSFER_TO_TYPE => isset($input[ToType::ACCOUNT]) ? ToType::ACCOUNT : ToType::CUSTOMER,
            Entity::PLATFORM_TRANSFER => $input[Entity::PLATFORM_TRANSFER] ?? false
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

    public function pushTransferTransactionCreationDelayMetrics($latency, $category, $sourceType)
    {
        $dimensions = [
            self::TRANSFER_ROUTE        => $this->getRouteName(),
            self::TRANSFER_SOURCE       => $sourceType,
            self::MERCHANT_CATEGORY     => $category,
        ];

        $this->trace->histogram(
            self::PG_LEDGER_TRANSFER_TRANSACTION_CREATION_DELAY,
            $latency,
            $dimensions
        );
    }

    public function pushPendingTransfersCount($paymentTransfersCount, $orderTransfersCount, $category)
    {
        $dimensions = ['category' => $category];

        $this->trace->gauge(self::PENDING_PAYMENT_TRANSFERS_COUNT, $paymentTransfersCount, $dimensions);

        $this->trace->gauge(self::PENDING_ORDER_TRANSFERS_COUNT, $orderTransfersCount, $dimensions);
    }

    public function pushWebhookDispatchFailureMetrics()
    {
        $dimensions = $this->getCreateDefaultDimensions();

        $this->trace->count(self::TRANSFER_WEBHOOK_DISPATCH_FAILURE, $dimensions);
    }

    public function pushMetricForTransferTransactionsCreate(\Throwable $e)
    {
        $this->pushExceptionMetrics($e, self::TRANSFER_TRANSACTION_CREATE_FAILED, $this->getCreateDefaultDimensions());
    }
}
