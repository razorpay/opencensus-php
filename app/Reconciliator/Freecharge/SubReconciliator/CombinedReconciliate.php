<?php

namespace RZP\Reconciliator\Freecharge;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TRANSACTION_TYPE = 'transaction_type';

    const TXN_TYPE_PAYMENT = 'Payment';

    const TXN_TYPE_PAYMENT_REVERSAL = 'Payment Reversal';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::TXN_TYPE_PAYMENT          => BaseReconciliate::PAYMENT,
        self::TXN_TYPE_PAYMENT_REVERSAL => BaseReconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = $row[self::COLUMN_TRANSACTION_TYPE];

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null;
    }
}
