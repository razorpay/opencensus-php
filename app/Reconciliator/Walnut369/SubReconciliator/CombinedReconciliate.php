<?php

namespace RZP\Reconciliator\Walnut369\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Walnut369\Reconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PAYMENT = 'disbursal';
    const REFUND  = 'cancellation';

    const BLACKLISTED_COLUMNS = [];

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT => Base\Reconciliate::PAYMENT,
        self::REFUND  => Base\Reconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = trim(strtolower($row[Reconciliate::TXN_TYPE] ?? null));

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null;
    }
}
