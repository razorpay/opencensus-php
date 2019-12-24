<?php

namespace RZP\Reconciliator\VasAxis\SubReconciliator;

use RZP\Reconciliator\Base;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_AMOUNT = 'gross_amt';

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        // If the amount is negative then its a refund
        if (isset($row[self::COLUMN_AMOUNT]) === true)
        {
            $amount = $row[self::COLUMN_AMOUNT];

            if ($amount < 0)
            {
                return Base\Reconciliate::REFUND;
            }
            else
            {
                return Base\Reconciliate::PAYMENT;
            }
        }

        return self::NA;
    }
}
