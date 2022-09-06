<?php

namespace RZP\Reconciliator\CardlessEmiZestMoney\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\CardlessEmiZestMoney\Reconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const PAYMENT = 'forward';
    const REFUND =  'reverse';
    const ACCOUNT_HOLDER_ID = 'account_holder_id';
    const ACCOUNT_ID = 'accountid';

    const BLACKLISTED_COLUMNS = [
        self::ACCOUNT_HOLDER_ID,
        self::ACCOUNT_ID,
    ];

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
