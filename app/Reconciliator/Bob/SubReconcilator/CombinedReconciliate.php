<?php

namespace RZP\Reconciliator\Bob;

use RZP\Reconciliator\Base;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    const PURCHASE_TXN = 'purchase';
    const REFUND_TXN   = 'refund';

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
        $transactionType = strtolower($row[ReconcilationFields::TRANSACTION_TYPE]);

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null ;
    }
}
