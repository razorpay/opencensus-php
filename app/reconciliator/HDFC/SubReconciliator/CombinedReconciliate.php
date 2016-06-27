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
        $entityType = trim($row[self::COLUMN_ENTITY_TYPE]);

        if ($entityType === 'CVD')
        {
            return BaseReconciliate::REFUND;
        }
        else if ($entityType === 'BAT')
        {
            return BaseReconciliate::PAYMENT;
        }
        else if (empty($entityType) === true)
        {
            return self::NA;
        }
        else
        {
            return null;
        }
    }
}