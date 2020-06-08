<?php

namespace RZP\Reconciliator\BajajFinserv\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_ENTITY_TYPE        = 'type_of_txn';
    const COLUMN_PAYMENT            = 'sale';
    const COLUMN_REFUND             = 'refund';

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_ENTITY_TYPE]) === false)
        {
            return null;
        }

        if (strtolower($row[self::COLUMN_ENTITY_TYPE]) === self::COLUMN_PAYMENT)
        {
            return BaseReconciliate::PAYMENT;
        }
        else if (strtolower($row[self::COLUMN_ENTITY_TYPE]) === self::COLUMN_REFUND)
        {
            return BaseReconciliate::REFUND;
        }
        else
        {
            return self::NA;
        }
    }
}
