<?php

namespace RZP\Reconciliator\Mobikwik;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    // ----- Row header names -----
    const COLUMN_REFUND_AMOUNT    = 'RefundAmount';

    /**
     * There is no column defining whether the row is refund or payment
     * Hence we check the value of 'COLUMN_REFUND_AMOUNT'
     *
     * @param $row array
     */
    protected function getReconciliationTypeForRow(array $row)
    {
        if ($row[self::COLUMN_REFUND_AMOUNT] !== 'None')
        {
            return BaseReconciliate::REFUND;
        }

        return BaseReconciliate::PAYMENT;
    }
}
