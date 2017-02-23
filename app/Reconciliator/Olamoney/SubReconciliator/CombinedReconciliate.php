<?php

namespace RZP\Reconciliator\Olamoney;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE  = 'Transaction Type';

    protected function getReconciliationTypeForRow($row)
    {
        if ($row[self::COLUMN_ENTITY_TYPE] === 'debit')
        {
            return BaseReconciliate::PAYMENT;
        }
        else if ($row[self::COLUMN_ENTITY_TYPE] === 'refund')
        {
            return BaseReconciliate::REFUND;
        }
        else
        {
            return null;
        }
    }
}