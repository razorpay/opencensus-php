<?php

namespace RZP\Reconciliator\checkout_dot_com\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const BLACKLISTED_COLUMNS = [];
    
    protected function getReconciliationTypeForRow($row)
    {
        $entityType = null;

        //
        // Identifies if row type is payment or refund.
        //
        $reconType = null;

        if (isset($row[ReconciliationFields::BREAKDOWN_TYPE]) === true)
        {
            $entityType = $row[ReconciliationFields::BREAKDOWN_TYPE];

            $entityType = trim($entityType);
        }

        if (blank($entityType) === true)
        {
            $reconType = self::NA;
        }
        else if ($entityType === 'Refund')
        {
            $reconType = BaseReconciliate::REFUND;
        }
        else if ($entityType === 'Capture')
        {
            $reconType = BaseReconciliate::PAYMENT;
        }
        else
        {
            $reconType = self::NA;
        }

        return $reconType;
    }
}
