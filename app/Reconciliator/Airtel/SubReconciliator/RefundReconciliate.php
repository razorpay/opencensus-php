<?php

namespace RZP\Reconciliator\Airtel\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';
    const COLUMN_PAYMENT_ID         = 'partner_txn_id';
    const COLUMN_REFUND_AMOUNT      = 'original_input_amt';
    const COLUMN_RZP_REFUND_ID      = 'refund_id';

    protected function getRefundId($row)
    {
        return $row[self::COLUMN_RZP_REFUND_ID];
    }

    protected function validateRefundAmountEqualsReconAmount(array $row): bool
    {
        if ($this->refund->getBaseAmount() !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
                    'gateway'           => $this->refund->getGateway(),
                ]);

            return false;
        }

        return true;
    }
}
