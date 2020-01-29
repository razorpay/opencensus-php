<?php

namespace RZP\Reconciliator\Jiomoney\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TRANSACTION_TYPE   = ['payment_instrument', 'transaction_type'] ;

    const TXN_TYPE_SALE     = 'sale';
    const TXN_TYPE_DEBIT    = 'debit';
    const TXN_TYPE_REFUND   = 'refund';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::TXN_TYPE_SALE     => BaseReconciliate::PAYMENT,
        self::TXN_TYPE_DEBIT    => BaseReconciliate::PAYMENT,
        self::TXN_TYPE_REFUND   => BaseReconciliate::REFUND,
    ];

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        //
        // In recent times, we are getting the transaction type i.e. Sale/Refund
        // in payment_instrument column. Earlier it used to be under transaction_type
        // column. So taking into account both the cases to find the recon type.
        //
        foreach (self::COLUMN_TRANSACTION_TYPE as $col)
        {
            $transactionType = strtolower($row[$col] ?? null);

            if (empty(self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType]) === false)
            {
                return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType];
            }
        }

        return self::NA;
    }
}
