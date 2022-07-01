<?php

use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE = 'TRANSACTION_TYPE';

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        $entityType = null;

        //
        // Identifies if row type is payment or refund.
        //
        $reconType = null;

        if (isset($row[self::COLUMN_ENTITY_TYPE]) === true)
        {
            $entityType = $row[self::COLUMN_ENTITY_TYPE];

            $entityType = trim($entityType);
        }

        if (blank($entityType) === true)
        {
            $reconType = self::NA;
        }
        else if ($entityType === 'PAYMENT')
        {
            $reconType = BaseReconciliate::PAYMENT;
        }
        else if ($entityType === 'REFUND')
        {
            $reconType = BaseReconciliate::REFUND;
        }
    }
}
