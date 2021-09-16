<?php

namespace RZP\Reconciliator\checkout_dot_com\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Refund\Status;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const NA = 'not_applicable';
    const COLUMN_REFUND_AMOUNT      = ReconciliationFields::PROCESSING_CURRENCY_AMOUNT;

    protected function getRefundId($row)
    {
        return $row[ReconciliationFields::REFERENCE] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $refundReconStatus = $row[ReconciliationFields::BREAKDOWN_TYPE];
        $approvalStatus = $row[ReconciliationFields::RESPONSE_DESCRIPTION];

        if ($refundReconStatus == 'Refund' && $approvalStatus == 'Approved')
        {
            return  Status::PROCESSED;
        }
        else
        {
            return self::NA;
        }
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        $expectedCurrency = $this->payment->getGatewayCurrency();

        $reconCurrency = $row[ReconciliationFields::PROCESSING_CURRENCY] ?? null;

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
}
