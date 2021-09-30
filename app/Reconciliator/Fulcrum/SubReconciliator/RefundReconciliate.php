<?php

namespace RZP\Reconciliator\Fulcrum\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Base\UniqueIdEntity;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID              = 'invoice_number';
    const COLUMN_ARN                    = 'arn';
    const COLUMN_AUTH_CODE              = 'auth_id';
    const COLUMN_FEE                    = 'fee_amount';
    const COLUMN_REFUND_AMOUNT          = 'amount';
    const DEFAULT_CURRENCY_CODE         = '356';

    /**
     * Refund id has been set in invoice_number
     * column, while preprocessing in reconciliate.
     *
     * @param array   $row
     * @return string $refundId
     */
    protected function getRefundId($row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        if (empty($refundId) === false)
        {
            if (UniqueIdEntity::verifyUniqueId($refundId, false) === false)
            {
                return substr($row[self::COLUMN_REFUND_ID], 1, 14);

                return $refundId;
            }
            return $refundId;
        }

        return null;
    }

    protected function getArn(array $row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            return null;
        }

        return $row[self::COLUMN_ARN];
    }
}
