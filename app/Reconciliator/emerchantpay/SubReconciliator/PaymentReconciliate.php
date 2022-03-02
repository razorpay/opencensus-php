<?php

namespace RZP\Reconciliator\emerchantpay\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\Base\SubReconciliator;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    const NA = 'not_applicable';

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        return $row[ReconciliationFields::MERCHANT_TRANSACTION_ID] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $transactionType = $row[ReconciliationFields::TRANSACTION_TYPE];

        if ($transactionType === ReconciliationFields::SALE_APPROVED)
        {
            return Status::CAPTURED;
        }
        else
        {
            return self::NA;
        }
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getGatewayAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getGatewayAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getGatewayCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $expectedCurrency = $this->payment->getGatewayCurrency();

        $reconCurrency = $row[ReconciliationFields::TRANSACTION_CURRENCY] ?? null;

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::CURRENCY_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }



    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[ReconciliationFields::TRANSACTION_AMOUNT]) === true)
        {
            return null;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::TRANSACTION_AMOUNT]);
    }
}
