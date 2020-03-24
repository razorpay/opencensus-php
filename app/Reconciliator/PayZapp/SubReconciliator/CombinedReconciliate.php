<?php

namespace RZP\Reconciliator\PayZapp\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE  = 'transaction_type';

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_ENTITY_TYPE]) === false)
        {
            return null;
        }

        $txnType = strtolower($row[self::COLUMN_ENTITY_TYPE]);

        if ($txnType === 'sale')
        {
            return BaseReconciliate::PAYMENT;
        }
        else if ($txnType === 'refund')
        {
            return BaseReconciliate::REFUND;
        }
        else
        {
            return self::NA;
        }
    }
}
