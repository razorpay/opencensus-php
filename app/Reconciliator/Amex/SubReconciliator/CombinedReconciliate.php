<?php

namespace RZP\Reconciliator\Amex\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_ENTITY_TYPE  = 'type';
    const COLUMN_PAYMENT      = 'sale';
    const COLUMN_REFUND       = 'credit';

    // Refund cannot be processed as we dont the refund id
    // in the recon file
    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::COLUMN_PAYMENT => BaseReconciliate::PAYMENT,
        self::COLUMN_REFUND  => BaseReconciliate::REFUND,
    ];

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_ENTITY_TYPE]) === false)
        {
            return null;
        }

        $txnType = strtolower($row[self::COLUMN_ENTITY_TYPE]);

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType] ?? self::NA;
    }
}
