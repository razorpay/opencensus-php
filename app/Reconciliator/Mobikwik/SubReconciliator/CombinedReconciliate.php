<?php

namespace RZP\Reconciliator\Mobikwik\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT      = 'payout initiated';
    const COLUMN_REFUND       = 'refund adjusted';
    const COLUMN_STATUS       = 'status';
    const COLUMN_USER_EMAIL   = 'useremail';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::COLUMN_PAYMENT => BaseReconciliate::PAYMENT,
        self::COLUMN_REFUND  => BaseReconciliate::REFUND,
    ];

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_USER_EMAIL,
    ];

    /**
     * After a brief discussion with gateway owner for
     * mobikwik from finops, have changed the logic for
     * getting recon type for row.
     *
     * @param $row array
     *
     * @return null|string
     */
    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_STATUS]) === false)
        {
            return null;
        }

        $txnType = strtolower($row[self::COLUMN_STATUS]);

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType] ?? null;
    }
}
