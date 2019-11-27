<?php

namespace RZP\Reconciliator\BajajFinserv\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const COLUMN_AMOUNT = 'amount_financed_rs';

    const COLUMN_GATEWAY_TRANSACTION_ID = 'rrn';

    protected function getPaymentId(array $row)
    {
        return $row['asset_serial_numberimei'] ?? null;
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

        if ($paymentAmount !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_amount'   => $paymentAmount,
                    'recon_amount'      => $this->getReconPaymentAmount($row),
                    'currency'          => $this->payment->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
