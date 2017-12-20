<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TXN_TYPE = 'transaction_type';

    const PURCHASE_TXN    = 'PURCHASE';
    const REFUND_TXN      = 'REFUND (CREDIT)';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN => BaseReconciliate::PAYMENT,
        self::REFUND_TXN   => BaseReconciliate::REFUND
    ];

    /**
     * Column 'TRANSACTION_TYPE' in excel indicates whether
     * txn is payment or refund
     *
     * For payment, value is 'PURCHASE'
     * For refund, value is 'REFUND (CREDIT)'
     *
     * @param array $row
     * @return string
     */
    protected function getReconciliationTypeForRow($row)
    {
        //
        // If the "transaction_type" columnheader is not present
        // in the parsed row, we return null, as this is an invalid
        // or wrongly formatted row.
        //
        if (isset($row[self::COLUMN_TXN_TYPE]) === false)
        {
            return null;
        }

        $txnType = $row[self::COLUMN_TXN_TYPE];

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType] ?? self::NA;
    }
}
