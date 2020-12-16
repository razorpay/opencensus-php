<?php

namespace RZP\Models\Batch\Processor\Emandate\CancelDebit;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Batch\Processor\Emandate\Base as BaseProcessor;

class All extends BaseProcessor
{
    const PAYMENT_ID = 'payment_id';

    protected function processEntry(array & $entry)
    {
        $paymentId = $entry[self::PAYMENT_ID];

        $this->updatePaymentEntities($paymentId);

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    protected function updatePaymentEntities(string $paymentId)
    {
        $payment = $this->getPayment($paymentId);

        $this->processCancelPayment($payment);
    }

    protected function getPayment(string $paymentId)
    {
        return $this->repo->payment->findOrFailPublic($paymentId);
    }

    protected function processCancelPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        try
        {
            $processor->cancelEmandatePayment($payment);
        }
        catch (\Throwable $e)
        {
            if (($e instanceof BadRequestException) and
                ($e->getError()->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER))
            {
                $this->trace->info(
                    TraceCode::PAYMENT_CANCELLED,
                    [
                        'data' => $e->getError(),
                    ]
                );
            }
            else
            {
                throw $e;
            }
        }
    }
}
