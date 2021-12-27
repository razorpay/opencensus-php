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

    const JOB_RELEASE_DELAY = 5; // In seconds.

    protected $mutex;

    protected $repo;

    protected $payment;

    protected $transferMode;

    protected $jobDeleteFlag;

    public $timeout = 900;

    protected $queueConfigKey = 'transfer_process';

    public function __construct(string $mode, $payment, $transfermode = Transfer\Constant::ORDER)
    {
        parent::__construct($mode);

        $this->payment = $payment;

        $this->transferMode = $transfermode;

        $this->jobDeleteFlag = true;
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

        }catch (\Exception $ex)
        {
            if ($ex->getMessage() === Transfer\Constant::MUTEX_LOCK_ON_LINKED_ACCOUNT_ID_NOT_ACQUIRED)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::TRANSFER_PROCESS_JOB_RELEASE_SINCE_MUTEX_ACQUIRE_FAILED,
                    [
                        'payment_id'    => $this->payment->getId(),
                        'source_type'   => $this->transferMode,
                        'job_attempts'  => $this->attempts(),
                    ]
                );

                $this->jobDeleteFlag = false;

                $this->release(self::JOB_RELEASE_DELAY);
            }

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
            if ($this->jobDeleteFlag === true)
            {
                $this->delete();
            }
        }
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
