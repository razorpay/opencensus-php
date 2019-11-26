<?php

namespace RZP\Reconciliator\CardFssBob\SubReconciliator;

use RZP\Reconciliator\Base;

/**
 * Class CombinedReconciliate
 * @see https://docs.google.com/spreadsheets/d/1T8SHup7_Jgzk2jYYS_D3x--zcU8nrGwyq_0M7UGi3ro/edit?usp=sharing
 */
class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PURCHASE_TXN = 'purchase';
    const REFUND_TXN   = 'refund';

    const BLACKLISTED_COLUMNS = [];

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN => Base\Reconciliate::PAYMENT,
        self::REFUND_TXN   => Base\Reconciliate::REFUND
    ];

    /**
     * Column 'Transaction Type' in excel indicates whether
     * txn is payment or refund
     *
     * For payment, value is 'Purchase'
     * For refund, value is 'Refund'
     *
     * @param array $row
     * @return string|null if invalid transaction type is passed
     */
    protected function getReconciliationTypeForRow($row)
    {
        $columnTransactionType = array_first(ReconciliationFields::TRANSACTION_TYPE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $transactionType = strtolower($row[$columnTransactionType]);

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null ;
    }
}
