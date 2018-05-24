<?php

namespace RZP\Reconciliator\PayZapp;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE  = 'transaction_type';

    protected function getReconciliationTypeForRow($row)
    {
        if ($row[self::COLUMN_ENTITY_TYPE] === 'Sale')
        {
            return BaseReconciliate::PAYMENT;
        }
        else if ($row[self::COLUMN_ENTITY_TYPE] === 'Refund')
        {
            return BaseReconciliate::REFUND;
        }
        else
        {
            return null;
        }
    }
}