<?php

namespace RZP\Reconciliator\IndusindDebitEmi\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\IndusindDebitEmi\Reconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const SETTLED = 'settled';
    const REFUND =  'refund';
    const BLACKLISTED_COLUMNS = [];

    const TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP = [
        self::SETTLED => Base\Reconciliate::PAYMENT,
        self::REFUND  => Base\Reconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $txStatus = trim(strtolower($row[Reconciliate::TX_STATUS] ?? null));

        return self::TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP[$txStatus] ?? null;
    }
}
