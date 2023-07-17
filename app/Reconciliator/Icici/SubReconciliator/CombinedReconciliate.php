<?php
namespace RZP\Reconciliator\Icici\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Icici\Reconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const SALE = 'sale';
    const REFUND =  'refund';

    const BLACKLISTED_COLUMNS = [];

    const TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP = [
        self::SALE => Base\Reconciliate::PAYMENT,
        self::REFUND  => Base\Reconciliate::REFUND
    ];

    protected function getReconciliationTypeForRow($row)
    {

        $columnTransactionType = array_first(ReconciliationFields::ACTION_CODE, function ($col) use ($row)
        {
            return (empty($row[$col]) === false);
        });

        $transactionType = strtolower($row[$columnTransactionType]);

        return self::TRANSACTION_STATUS_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? null;
    }
}
