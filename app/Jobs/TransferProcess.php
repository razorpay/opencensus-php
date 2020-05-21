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

        $this->payment = $this->CheckforBackWardCompatile($this->payment);;

        $this->trace->info(
            TraceCode::TRANSFER_PROCESS_QUEUE,
            [
                'payment_id'   => $this->payment->getPublicId(),
                'transfermode' => $this->transferMode
            ]
        );

        try
        {
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
        finally
        {
            $this->delete();
        }
    }

    private  function CheckforBackWardCompatile($payment)
    {
         $app = App::getFacadeRoot();

         $this->repo = $app['repo'];

        $paymentId = null;

        try
        {
            if ($payment instanceof Payment\Entity)
            {
                $this->trace->info(
                    TraceCode::TRANSFER_MESSAGE_OLD_FORMAT,
                    [
                        'payment_id'   => $this->payment->getPublicId(),
                        'transfermode' => $this->transferMode
                    ]
                );

                $paymentId = $payment->getId();

            } else
            {
                $paymentId = $payment;
            }

            return $this->repo->payment->findOrFailPublic($paymentId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TRANSFER_PROCESS_PAYMENT_ID_NOT_FOUND,
                [
                    'message'     => 'paymentId not found, deleting the message from queue',
                    'payment_id'   => $this->payment->getPublicId(),
                    'transfermode' => $this->transferMode,
                ]
            );

            $this->delete();
        }
    }
}
