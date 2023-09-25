<?php

namespace RZP\Jobs;

use App;
use Exception;
use RZP\Models\Transfer\Core;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Transfer\Metric;
use RZP\Base\RepositoryManager;
use RZP\Models\Transfer\Status;
use Illuminate\Foundation\Application;

/**
 * Job class to update the balance for the transfer transaction.
 * Supported only for EtHJCtiuRSZRCz (Airtel) as of now
 *
 */
class AsyncBalanceUpdateForTransfer extends Job
{
    protected $queueConfigKey = 'transfer_process_capital_float';

    /**
     * The transaction ID
     *
     * @var string
     */
    protected $transactionId;

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

    public function __construct(string $mode, $transactionId, $transferId)
    {
        parent::__construct($mode);

        $this->transactionId = $transactionId;

        $this->transferId = $transferId;

        $this->merchantId = 'EtHJCtiuRSZRCz';
    }

    public function handle()
    {
        parent::handle();

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $startTime = microtime(true);

        $this->trace->info(
            TraceCode::ASYNC_BALANCE_UPDATE_FOR_TRANSFER_TRANSACTION,
            [
                'transaction_id'   => $this->transactionId,
                'transfer_id'      => $this->transferId
            ]
        );

        try
        {
            $transfer = $this->repo->transfer->findByIdAndMerchantId($this->transferId, $this->merchantId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ASYNC_BALANCE_UPDATE_TRANSFER_NOT_FOUND,
                [
                    'message'          => 'Transfer not found',
                    'transaction_id'   => $this->transactionId,
                    'transfer_id'      => $this->transferId,
                ]
            );

            (new Metric())->pushAsyncBalanceUpdateForTransferTxnFailedMetrics(true, false, false);

            return;
        }

        if ($transfer->merchant->getId() !== $this->merchantId)
        {
            $this->trace->info(TraceCode::ASYNC_BALANCE_UPDATE_CALLED_FOR_NON_ENABLED_MERCHANT,
                [
                    'transaction_id'   => $this->transactionId,
                    'transfer_id'      => $this->transferId,
                    'merchant_id'      => $transfer->merchant->getId()
                ]
            );

            return;
        }

        if ($transfer->getStatus() === Status::PENDING)
        {
            $this->trace->info(TraceCode::ASYNC_BALANCE_UPDATE_TXN_SKIPPED,
                [
                    'transaction_id'   => $this->transactionId,
                    'transfer_id'      => $this->transferId,
                    'merchant_id'      => $transfer->merchant->getId(),
                    'transfer_status'  => Status::PENDING
                ]
            );

            return;
        }

        try
        {
            $transaction = $this->repo->transaction->findByIdAndMerchantId($this->transactionId, $this->merchantId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ASYNC_BALANCE_UPDATE_TXN_NOT_FOUND,
                [
                    'message'          => 'Transaction not found',
                    'transaction_id'   => $this->transactionId,
                    'transfer_id'      => $this->transferId,
                ]
            );

            (new Metric())->pushAsyncBalanceUpdateForTransferTxnFailedMetrics(false, true, false);

            return;
        }

        if ($transaction->isBalanceUpdated() === true)
        {
            $this->trace->info(TraceCode::TRANSACTION_BALANCE_ALREADY_UPDATED,
                [
                    'transaction_id'   => $this->transactionId,
                    'transfer_id'      => $this->transferId
                ]
            );

            return;
        }

        try
        {
            (new Core())->updateBalanceAsyncForTransferTxn($transaction);

            (new Metric())->pushAsyncBalanceUpdateForTransferTxnSuccessMetrics($startTime);

            $this->trace->info(TraceCode::ASYNC_BALANCE_UPDATE_TXN_SUCCESSFUL,
                [
                    'transaction_id'   => $this->transactionId,
                    'transfer_id'      => $this->transferId,
                    'transfer_status'  => $transfer->getStatus(),
                ]
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::ASYNC_BALANCE_UPDATE_FAILED_FOR_TRANSFER_TXN,
                [
                    'message'          => 'Async balance update failed',
                    'transaction_id'   => $this->transactionId,
                    'transfer_id'      => $this->transferId,
                ]
            );

            (new Metric())->pushAsyncBalanceUpdateForTransferTxnFailedMetrics(false, false, true);
        }
    }
}
