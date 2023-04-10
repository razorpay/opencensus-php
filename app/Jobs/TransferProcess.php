<?php

namespace RZP\Jobs;

use App;
use RZP\Constants\Entity;
use RZP\Models\Payment;
use RZP\Models\Transfer;
use RZP\Trace\TraceCode;

class TransferProcess extends Job
{
    const MUTEX_LOCK_TIMEOUT = 600;

    protected $mutex;

    protected $repo;

    protected $payment;

    protected $transferMode;

    public $timeout = 900;

    protected $queueConfigKey = 'transfer_process';

    public function __construct(string $mode, $payment, $transfermode = Transfer\Constant::ORDER)
    {
        parent::__construct($mode);

        $this->payment = $payment;

        $this->transferMode = $transfermode;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::TRANSFER_PROCESS_QUEUE,
            [
                'payment_id'   => $this->payment,
                'transfermode' => $this->transferMode
            ]
        );

        try
        {
            $this->payment = $this->getPaymentEntity($this->payment);

            // if the balance is not update we would further delay the transfer processing
            // this happens for merchants who are on aysnc balance update flow
            $delay  = $this->checkProcessingDelay($this->payment);

            if ($delay === true)
            {
                (new Transfer\Core)->dispatchForTransferProcessing($this->transferMode, $this->payment);

                $this->delete();

                return;
            }

            $transfer = null;

            if ($this->transferMode === Transfer\Constant::ORDER)
            {
                $transfer = new Transfer\OrderTransfer($this->payment);
            }
            else
            {
                $transfer = new Transfer\PaymentTransfer($this->payment);
            }

            $transfer->process();

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
        }
        finally
        {
            $this->delete();
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

    private  function getPaymentEntity($paymentId)
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
                    'message'     => 'paymentId not found',
                    'payment_id'   => $this->payment,
                    'transfermode' => $this->transferMode,
                ]
            );

            throw $ex;
        }
    }
}
