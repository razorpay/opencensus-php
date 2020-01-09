<?php

namespace RZP\Reconciliator\BajajFinserv\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_AMOUNT = 'amount_financed_rs';

    const COLUMN_GATEWAY_TRANSACTION_ID = 'rrn';

    const COLUMN_PAYMENT_ID = 'asset_serial_numberimei';

    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_PAYMENT_ID] ?? null;
    }

    protected function getArn($row)
    {
        return $row['utr_no'] ?? null;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_AMOUNT] ?? null);
    }

    protected function getGatewayTransactionId(array $row)
    {
        if (empty($row[self::COLUMN_GATEWAY_TRANSACTION_ID]) === true)
        {
            return null;
        }

        return trim($row[self::COLUMN_GATEWAY_TRANSACTION_ID]);
    }

    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayPayment)
    {
        $body = $gatewayPayment->getData();

        $body['DealID'] = $gatewayTransactionId;

        $attributes['raw'] = json_encode($body);

        $gatewayPayment->fill($attributes);
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $paymentAmount = ($convertCurrency === true) ? $this->payment->getBaseAmount() : $this->payment->getAmount();

        // Ceil the amount
        // BFL sends us the amount in Rs always.
        // Ex: If amount is Rs 12002.04, BFL sends us Rs 12003
        $paymentAmount = (int)(ceil($paymentAmount / 100) * 100);

        $reconAmount = $this->getReconPaymentAmount($row);

        if ($paymentAmount !== $reconAmount)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_amount'   => $paymentAmount,
                    'recon_amount'      => $reconAmount,
                    'expected_amount_type'   => gettype($paymentAmount),
                    'recon_amount_type'      => gettype($reconAmount),
                    'currency'          => $this->payment->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
