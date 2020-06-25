<?php

namespace RZP\Reconciliator\BajajFinserv\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_RRN            = 'rrn';

    const COLUMN_ARN            = 'utr_no';

    const TRANSACTION_DATE      = 'transaction_date';

    const COLUMN_PAYMENT_ID     = 'asset_serial_numberimei';

    const COLUMN_PAYMENT_AMOUNT = 'amount_financed_rs';

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID] ?? null;
    }

    protected function getArn($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_RRN] ? trim($row[self::COLUMN_RRN]) : null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[self::TRANSACTION_DATE] ?? null;
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $paymentAmount = ($convertCurrency === true) ? $this->payment->getBaseAmount() : $this->payment->getGatewayAmount();

        // Ceil the amount
        // BFL sends us the amount in Rs always.
        // Ex: If amount is Rs 12002.04, BFL sends us Rs 12003
        $paymentAmount = (int)(ceil($paymentAmount / 100) * 100);

        $reconAmount = $this->getReconPaymentAmount($row);

        if ($paymentAmount !== $reconAmount)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'           => TraceCode::RECON_INFO_ALERT,
                    'info_code'            => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'           => $this->payment->getId(),
                    'expected_amount'      => $paymentAmount,
                    'recon_amount'         => $reconAmount,
                    'expected_amount_type' => gettype($paymentAmount),
                    'recon_amount_type'    => gettype($reconAmount),
                    'currency'             => $this->payment->getCurrency(),
                    'gateway'              => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
