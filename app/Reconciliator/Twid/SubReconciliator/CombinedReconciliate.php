<?php

namespace RZP\Reconciliator\Twid\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Twid\Reconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PAYMENT = 'capture';
    const REFUND  = 'refund';

    const BLACKLISTED_COLUMNS = [];

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT => Base\Reconciliate::PAYMENT,
        self::REFUND  => Base\Reconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = trim(strtolower($row[Reconciliate::STATUS] ?? null));

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null;
    }
}
