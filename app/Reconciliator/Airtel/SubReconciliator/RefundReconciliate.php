<?php

namespace RZP\Reconciliator\Airtel\SubReconciliator;

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

    protected function getGatewayAmount(array $row)
    {
        return $row[self::COLUMN_REFUND_AMOUNT];
    }

    protected function getArn($row)
    {
        return $row[self::COLUMN_GATEWAY_PAYMENT_ID];
    }
}
