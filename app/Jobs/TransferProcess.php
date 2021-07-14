<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Transfer\Constant as TransferConstant;
use RZP\Models\Transfer\OrderTransfer as OrderTransferProcessor;
use RZP\Models\Transfer\PaymentTransfer as PaymentTransferProcessor;

class TransferProcess extends Job
{
    const MUTEX_LOCK_TIMEOUT = 600;

    protected $mutex;

    protected $repo;

    protected $sourceType;

    protected $paymentId;

    protected $queueConfigKey = 'transfer_process';

    public $timeout = 900;

    public function __construct(string $mode, string $sourceType, string $paymentId, $queueConfigKey = null)
    {
        parent::__construct($mode);

        $this->sourceType = $sourceType;

        $this->paymentId = $paymentId;

        if (empty($queueConfigKey) === false)
        {
            $this->queueConfigKey = $queueConfigKey;
        }
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::TRANSFER_PROCESS_JOB_STARTED,
            [
                'source_type'   => $this->sourceType,
                'payment_id'    => $this->paymentId,
            ]
        );

        try
        {
            $payment = $this->getPaymentEntity($this->paymentId);

            $transferProcessor = $this->getTransferProcessor($payment);

            $transferProcessor->process();

            $this->trace->info(
                TraceCode::TRANSFER_PROCESS_JOB_COMPLETED,
                [
                    'source_type'   => $this->sourceType,
                    'payment_id'    => $this->paymentId,
                ]
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TRANSFER_PROCESS_JOB_FAILED,
                [
                    'source_type'   => $this->sourceType,
                    'payment_id'    => $this->paymentId,
                ]
            );
        }
        finally
        {
            $this->delete();
        }
    }

    private function getPaymentEntity(string $id)
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        try
        {
            return $this->repo->payment->findOrFail($id);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::TRANSFER_PROCESS_INVALID_PAYMENT_ID,
                [
                    'source_type'   => $this->sourceType,
                    'payment_id'    => $id,
                ]
            );

            throw $ex;
        }
    }

    private function getTransferProcessor(Payment $payment)
    {
        if ($this->sourceType === TransferConstant::PAYMENT)
        {
            return new PaymentTransferProcessor($payment);
        }
        else
        {
            return new OrderTransferProcessor($payment);
        }
    }
}
