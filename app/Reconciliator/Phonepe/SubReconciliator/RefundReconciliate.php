<?php

namespace RZP\Reconciliator\Phonepe\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\WalletPhonepe\ReconFields;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    protected function getRefundId(array $row)
    {
        if (empty($row[ReconFields::RZP_ID]) === false)
        {
            //
            // Sometimes we get digits appended in refund ID
            // so take first 14 chars only.
            //
            return substr($row[ReconFields::RZP_ID], 0, 14);
        }

        return null;
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getBaseAmount() !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'payment_id'        => $this->refund->getPaymentId(),
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
                    'gateway'           => $this->refund->getGateway(),
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        if (array_key_exists(ReconFields::AMOUNT, $row) === false)
        {
            return null;
        }

        $formattedAmount = abs(Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconFields::AMOUNT]));

        return $formattedAmount;
    }

    protected function getArn(array $row)
    {
        return $row[ReconFields::BANK_REFERENCE_NO] ?? null;
    }
}
