<?php

namespace RZP\Reconciliator\checkout_dot_com\SubReconciliator;

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
        return $row[ReconciliationFields::REFERENCE] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[ReconciliationFields::BREAKDOWN_TYPE];
        $approvalStatus = $row[ReconciliationFields::RESPONSE_DESCRIPTION];

        if ($status === 'Capture' && $approvalStatus == 'Approved')
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

    protected function getReconPaymentAmount(array $row)
    {
        if (empty($row[ReconciliationFields::PROCESSING_CURRENCY_AMOUNT]) === true)
        {
            return null;
        }

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::PROCESSING_CURRENCY_AMOUNT]);
    }
}
