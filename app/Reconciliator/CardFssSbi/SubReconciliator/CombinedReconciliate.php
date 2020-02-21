<?php

namespace RZP\Reconciliator\CardFssSbi\SubReconciliator;

use RZP\Reconciliator\Base;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PURCHASE_TXN = 'purchase';
    const REFUND_TXN   = 'refund';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN => Base\Reconciliate::PAYMENT,
        self::REFUND_TXN   => Base\Reconciliate::REFUND
    ];

    const BLACKLISTED_COLUMNS = [
        ReconciliationFields::CARD_NO,
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = trim(strtolower($row[ReconciliationFields::TRANSACTION_TYPE] ?? null));

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null ;
    }
}
