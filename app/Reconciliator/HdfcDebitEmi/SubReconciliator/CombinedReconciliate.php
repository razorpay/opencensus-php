<?php

namespace RZP\Reconciliator\HdfcDebitEmi\SubReconciliator;

use RZP\Reconciliator\Base;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PURCHASE_TXN = 'payout done';
    const REFUND_TXN   = 'cancellation';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN => Base\Reconciliate::PAYMENT,
        self::REFUND_TXN   => Base\Reconciliate::REFUND
    ];

    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = trim(strtolower($row[ReconciliationFields::REMARKS] ?? null));

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null ;
    }

    protected function ignoreReconParseError(): bool
    {
        return true;
    }
}
