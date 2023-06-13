<?php

namespace RZP\Jobs;

use App;
use Exception;
use RZP\Constants\Entity;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer\Metric;
use RZP\Models\Transfer\Utility;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;

class TransferProcess extends Job
{
    const MUTEX_LOCK_TIMEOUT = 600;

    protected $mutex;

    protected $repo;

    protected $payment;

    protected $transferMode;

    public $timeout = 900;

    protected $queueConfigKey = 'transfer_process';

    protected const TRANSFER_FAILURE_RETRY_ATTEMPT = 2;

    public function __construct(string $mode, $payment, $transfermode = Transfer\Constant::ORDER)
    {
        parent::__construct($mode);

        $this->payment = $payment;

        $this->transferMode = $transfermode;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->payment = $this->getPaymentEntity($this->payment);

            // if the balance is not update we would further delay the transfer processing
            // this happens for merchants who are on aysnc balance update flow
            $delay = $this->checkProcessingDelay($this->payment);

            if ($delay === true)
            {
                // Dispatch with delay of 900s (15 min)
                (new Transfer\Core)->dispatchForTransferProcessing($this->transferMode, $this->payment, 900);

                $this->delete();

                return;
            }

            $this->trace->info(
                TraceCode::TRANSFER_PROCESS_QUEUE,
                [
                    'payment_id'   => $this->payment->getId(),
                    'transfermode' => $this->transferMode
                ]
            );

            $transfer = null;

            if ($this->transferMode === Transfer\Constant::ORDER)
            {
                $transfer = new Transfer\OrderTransfer($this->payment);
            }
            else
            {
                $transfer = new Transfer\PaymentTransfer($this->payment);
            }

            [, $failedTransfersToRetry] = $transfer->process();

            if(empty($failedTransfersToRetry) === false)
            {
                $this->checkRetry(Utility::INSUFFICIENT_BALANCE_RETRY_INTERVAL);

                return null;
            }

            $this->delete();

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TRANSFER_FAILURE,
                [
                    'message'     => 'transfer failed',
                    'payment_id'   => $this->payment,
                    'transfermode' => $this->transferMode,
                ]
            );

            (new Metric())->pushTransferProcessFailedMetrics($ex);

            if ((new Utility)->isRetryableError($ex) === true)
            {
                $retryTime = (new Utility)->getDelay($ex);

                $this->checkRetry($retryTime);
            }
        }
    }

    private function checkProcessingDelay($payment)
    {
        $transaction =  $payment->transaction;

        if ((empty($transaction) === true) or
            ($transaction->isBalanceUpdated() === false))
        {
            return true;
        }

        return false;
    }

    private function getPaymentEntity($paymentId)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        try
        {
            return $this->repo->payment->findOrFailPublic($paymentId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TRANSFER_PROCESS_PAYMENT_ID_NOT_FOUND,
                [
                    'message'      => 'paymentId not found',
                    'payment_id'   => $this->payment,
                    'transfermode' => $this->transferMode,
                ]
            );

            throw $ex;
        }
    }

    protected function checkRetry($delay)
    {
        if ($this->attempts() > self::TRANSFER_FAILURE_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::TRANSFER_FAILED_POST_ALL_RETRIES,
                [
                    'payment_id' => $this->payment,
                ]
            );

            $this->delete();
        }
        else
        {
            $this->trace->info(
                TraceCode::TRANSFER_FAILURE_RETRY_DISPATCH,
                [
                    'payment_id' => $this->payment,
                ]
            );

            $this->release($delay);
        }
    }

}
