<?php

namespace Reconciliator\HDFC;

use Reconciliator\Base;
use Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate  extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE  = 'rec_fmt';

    protected function getReconciliationTypeForRow($row)
    {
        if ($row[self::COLUMN_ENTITY_TYPE] === 'CVD')
        {
            return BaseReconciliate::REFUND;
        }
        else if ($row[self::COLUMN_ENTITY_TYPE] === 'BAT')
        {
            return BaseReconciliate::PAYMENT;
        }
        else
        {
            return null;
        }
    }
}