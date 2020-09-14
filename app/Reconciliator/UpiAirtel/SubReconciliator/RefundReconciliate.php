<?php

namespace RZP\Reconciliator\UpiAirtel\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_RRN                = 'partner_txn_id';
    const COLUMN_REFUND_ID          = 'till_id';
    const COLUMN_REFUND_AMOUNT      = 'original_input_amt';
    const COLUMN_GATEWAY_PAYMENT_ID = 'transaction_id';


    protected function getRefundId(array $row)
    {
        return $row[self::COLUMN_REFUND_ID] ?? null;
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
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
                    'gateway'           => $this->refund->getGateway(),
                ]);

            return false;
        }

        return true;
    }

    protected function getArn($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }
}
