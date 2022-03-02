<?php

namespace RZP\Reconciliator\emerchantpay\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Refund\Status;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const NA = 'not_applicable';
    const COLUMN_REFUND_AMOUNT = ReconciliationFields::TRANSACTION_AMOUNT;

    const BLACKLISTED_COLUMNS = [];

    protected function getRefundId($row)
    {
        return $row[ReconciliationFields::MERCHANT_TRANSACTION_ID] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $transactionType = $row[ReconciliationFields::TRANSACTION_TYPE];

        if ($transactionType === ReconciliationFields::REFUND_APPROVED)
        {
            return Status::PROCESSED;
        }
        else
        {
            return self::NA;
        }
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $reconAmount = $this->getReconRefundAmount($row);

        $refundAmount = $this->refund->getGatewayAmount();

        if ($reconAmount !== $refundAmount)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'       => $this->refund->getId(),
                    'expected_amount' => $this->refund->getBaseAmount(),
                    'recon_amount'    => $this->getReconRefundAmount($row),
                    'currency'        => $this->refund->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        $expectedCurrency = $this->refund->getGatewayCurrency();

        $reconCurrency = $row[ReconciliationFields::TRANSACTION_CURRENCY] ?? null;

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::CURRENCY_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'payment_id'        => $this->refund->payment->getId(),
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        if (empty($row[ReconciliationFields::TRANSACTION_AMOUNT]) === true)
        {
            return null;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::TRANSACTION_AMOUNT]);
    }
}
