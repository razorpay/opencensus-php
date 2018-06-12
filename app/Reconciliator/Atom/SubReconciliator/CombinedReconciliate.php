<?php

namespace RZP\Reconciliator\Atom;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    const COLUMN_TXN_STATE = 'txn_state';
    const COLUMN_REFUND_STATUS = 'refund_status';

    const PAYMENT_TXN = 'Sale';
    const REFUND_TXN   = 'Full Refund';
    const REFUND_TXN_2 = 'Partial Refund';

    // Refund cannot be reconned as we dont the refund id
    // in the recon file
    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT_TXN   => BaseReconciliate::PAYMENT,
    ];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_TXN_STATE]) === false)
        {
            return null;
        }

        $txnType = $row[self::COLUMN_TXN_STATE];

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType] ?? self::NA;
    }
}
