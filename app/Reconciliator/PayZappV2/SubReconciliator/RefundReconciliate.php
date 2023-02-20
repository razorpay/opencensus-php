<?php

namespace RZP\Reconciliator\PayZappV2\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    /*******************
     * Row Header Names
     *******************/
    const COLUMN_SERVICE_TAX       = ['cgst_amt', 'sgst_amt', 'igst_amt', 'utgst_amt', 'serv_tax'];
    const COLUMN_REFUND_ID         = 'udf2';
    const COLUMN_REFUND_AMOUNT     = 'domestic_amt';
    const COLUMN_GATEWAY_REFUND_ID = 'udf1';
    const COLUMN_UPI_REFUND_ID     = 'order_id';
    const COLUMN_UPI_REFUND_AMOUNT = 'transaction_amount';
    const COLUMN_UPI_RRN           = 'txn_ref_no_rrn';

    protected function getRefundId($row)
    {
        return $row[self::COLUMN_REFUND_ID] ?? $row[self::COLUMN_UPI_REFUND_ID];
    }

    protected function getReconRefundAmount(array $row): int
    {
        return Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT] ?? $row[self::COLUMN_UPI_REFUND_AMOUNT]);
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_GATEWAY_REFUND_ID] ?? $row[self::COLUMN_UPI_RRN];
    }

    protected function getArn($row)
    {
        return $this->getReferenceNumber($row);
    }
}
