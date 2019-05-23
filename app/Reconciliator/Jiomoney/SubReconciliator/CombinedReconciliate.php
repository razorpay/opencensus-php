<?php

namespace RZP\Reconciliator\Jiomoney\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TRANSACTION_TYPE   = 'transaction_type';
    const TXN_TYPE_PAYMENT          = 'Sale';
    const TXN_TYPE_REFUND           = 'Refund';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::TXN_TYPE_PAYMENT => BaseReconciliate::PAYMENT,
        self::TXN_TYPE_REFUND  => BaseReconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = $row[self::COLUMN_TRANSACTION_TYPE];

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? self::NA;
    }
}
