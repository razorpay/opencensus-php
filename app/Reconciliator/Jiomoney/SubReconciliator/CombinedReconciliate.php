<?php

namespace RZP\Reconciliator\Jiomoney;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TRANSACTION_TYPE = 'tran_type_identifier';

    const TXN_TYPE_JIOMONEY = 'Jio Money';

    const TXN_TYPE_JIOMONEY_WALLET = 'Jio Money Wallet';

    const TXN_TYPE_REFUND = 'Refund';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::TXN_TYPE_JIOMONEY        => BaseReconciliate::PAYMENT,
        self::TXN_TYPE_JIOMONEY_WALLET => BaseReconciliate::PAYMENT,
        self::TXN_TYPE_REFUND          => BaseReconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = $row[self::COLUMN_TRANSACTION_TYPE];

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null;
    }
}
