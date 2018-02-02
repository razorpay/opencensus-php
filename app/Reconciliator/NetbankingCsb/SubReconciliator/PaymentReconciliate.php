<?php

namespace RZP\Reconciliator\NetbankingCsb;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const PAYMENT_ID      = 'payment_id';
    const STATUS          = 'status';
    const AMOUNT          = 'amount';
    
    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    protected function getReconPaymentStatus(array $row)
    {
        return $row[self::STATUS] ?? 'Y';
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    private function getReconPaymentAmount(array $row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }
}
