<?php

namespace Reconciliator\Base\Foundation;


use Models\Payment;
use Trace\TraceCode;


class SubReconciliate
{
    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus === Payment\Status::FAILED)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Payment status is failed.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]);
        }
    }
}