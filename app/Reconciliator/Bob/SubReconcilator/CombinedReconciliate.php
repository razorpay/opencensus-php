<?php

namespace RZP\Reconciliator\Bob;

use RZP\Reconciliator\Base;

class CombinedReconciliate extends Base\CombinedReconciliate
{
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

        $reconciliationTypeMapping = [
            'purchase'  => Base\Reconciliate::PAYMENT,
            'refund'    => Base\Reconciliate::REFUND,
        ];

        return (isset($reconciliationTypeMapping[$transactionType]) === true) ? $reconciliationTypeMapping[$transactionType] : null;
    }
}
