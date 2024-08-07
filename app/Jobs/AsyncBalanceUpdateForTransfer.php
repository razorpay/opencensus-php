<?php

namespace RZP\Jobs;

use App;
use Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Transfer\Core;
use RZP\Models\Transfer\Constant;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Models\Feature;
use RZP\Models\Transfer\Metric;
use RZP\Base\RepositoryManager;
use RZP\Models\Transfer\Status;
use Illuminate\Foundation\Application;

/**
 * Job class to update the balance for the transfer transaction and transfer payment transactions
 *
 */
class AsyncBalanceUpdateForTransfer extends Job
{
    protected $queueConfigKey = 'transfer_process_capital_float';

    /**
     * The transfer ID
     *
     * @var string
     */
    protected $transferId;

    /**
     * The merchant ID
     *
     * @var string
     */
    protected $merchantId;

    /**
     * The application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var Mutex
     */
    protected $mutex;

    const MUTEX_LOCK_TIMEOUT_SEC = 1200;

    const MUTEX_RETRY_COUNT = 10;

    const MUTEX_MIN_RETRY_DELAY_MS = 1000;

    const MUTEX_MAX_RETRY_DELAY_MS = 5000;

    public function __construct(string $mode, $transferId)
    {
        parent::__construct($mode);

        $this->transferId = $transferId;
    }

    public function handle()
    {
        parent::handle();

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->mutex = $this->app['api.mutex'];

        $startTime = microtime(true);

        $this->trace->info(
            TraceCode::ASYNC_BALANCE_UPDATE_FOR_TRANSFER,
            [
                'transfer_id'      => $this->transferId,
                'attempt_count'    => $this->attempts()
            ]
        );

        try
        {
            $transfer = $this->repo->transfer->findOrFail($this->transferId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ASYNC_BALANCE_UPDATE_TRANSFER_NOT_FOUND,
                [
                    'message'          => 'Transfer not found',
                    'transfer_id'      => $this->transferId,
                ]
            );

            (new Metric())->pushAsyncBalanceUpdateForTransferFailedMetrics($ex);

            $this->delete();

            return;
        }

        if ($transfer->getStatus() === Status::PENDING ||
            $transfer->getStatus() === Status::CREATED ||
            $transfer->getStatus() === Status::FAILED)
        {
            $this->trace->info(TraceCode::ASYNC_BALANCE_UPDATE_TXN_SKIPPED,
                [
                    'transfer_id'      => $this->transferId,
                    'merchant_id'      => $transfer->merchant->getId(),
                    'transfer_status'  => $transfer->getStatus()
                ]
            );

            $this->delete();

            return;
        }

        try
        {
            $this->mutex->acquireAndRelease('async_bal_update_' . $transfer->getPublicId(),
                function () use ($transfer, $startTime)
                {
                    $this->repo->transaction(function () use ($transfer)
                    {
                        (new Core())->updateBalanceAsyncForTransferTxn($transfer);

                        (new Core())->updateBalanceAsyncForTransferPaymentTxn($transfer);
                    });
                },
                self::MUTEX_LOCK_TIMEOUT_SEC,
                ErrorCode::BAD_REQUEST_TRANSFER_ASYNC_BALANCE_UPDATE_IN_PROGRESS,
                self::MUTEX_RETRY_COUNT,
                self::MUTEX_MIN_RETRY_DELAY_MS,
                self::MUTEX_MAX_RETRY_DELAY_MS,
                true
            );

            (new Metric())->pushAsyncBalanceUpdateForTransferSuccessMetrics($startTime);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ASYNC_BALANCE_UPDATE_FAILED_FOR_TRANSFER,
                [
                    'message'          => 'Async balance update failed',
                    'transfer_id'      => $this->transferId,
                ]
            );

            (new Metric())->pushAsyncBalanceUpdateForTransferFailedMetrics($ex);

            $this->release(300);
        }
    }
}
