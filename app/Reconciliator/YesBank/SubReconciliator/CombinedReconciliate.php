<?php

namespace RZP\Reconciliator\YesBank\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_TXN_TYPE = 'transaction_type';

    const PURCHASE_TXN    = 'purchase';
    const REFUND_TXN      = 'refund';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN => BaseReconciliate::PAYMENT,
        self::REFUND_TXN   => BaseReconciliate::REFUND
    ];

    /**
     * Column 'TRANSACTION_TYPE' in excel indicates whether
     * txn is payment or refund
     *
     * For payment, value is 'PURCHASE'
     * For refund, value is 'REFUND'
     */
    protected function getReconciliationTypeForRow($row)
    {
        //
        // If the "transaction_type" column header is not present
        // in the parsed row, we return null, as this is an invalid
        // or wrongly formatted row.
        //
        if (isset($row[self::COLUMN_TXN_TYPE]) === false)
        {
            return null;
        }

        $txnType = strtolower($row[self::COLUMN_TXN_TYPE]);

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType] ?? self::NA;
    }
}
