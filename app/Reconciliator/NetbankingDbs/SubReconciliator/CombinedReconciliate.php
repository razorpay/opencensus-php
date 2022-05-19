<?php

namespace RZP\Reconciliator\NetbankingDbs\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\NetbankingDbs\Reconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PAYMENT = 'merchant_payment';
    const REFUND =  'merchant_payment_refund';

    const BLACKLISTED_COLUMNS = [];

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT => Base\Reconciliate::PAYMENT,
        self::REFUND  => Base\Reconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {
        $transactionType = trim(strtolower($row[Reconciliate::TRANSACTION_TYPE] ?? null));

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null;
    }
}
