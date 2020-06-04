<?php

namespace RZP\Reconciliator\Amex\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_PAYMENT      = 'sale';
    const COLUMN_REFUND       = 'credit';
    const COLUMN_AMOUNT       = 'charge_amount';

    // Refund cannot be processed as we dont the refund id
    // in the recon file
    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::COLUMN_PAYMENT => BaseReconciliate::PAYMENT,
        self::COLUMN_REFUND  => BaseReconciliate::REFUND,
    ];

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        $amount = strval($row[self::COLUMN_AMOUNT] ?? null);

        if ($amount === null)
        {
            return null;
        }

        return ($amount[0] === '-') ? BaseReconciliate::REFUND : BaseReconciliate::PAYMENT;
    }
}
