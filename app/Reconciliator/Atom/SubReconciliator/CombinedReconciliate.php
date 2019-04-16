<?php

namespace RZP\Reconciliator\Atom\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_TXN_STATE = 'txn_state';
    const COLUMN_REFUND_STATUS = 'refund_status';

    const PAYMENT_TXN = 'Sale';
    const REFUND_TXN   = 'Full Refund';
    const REFUND_TXN_2 = 'Partial Refund';
    const REFUND_TXN_3 = 'Auto Reversal';

    // Refund cannot be reconned as we dont the refund id
    // in the recon file
    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PAYMENT_TXN   => BaseReconciliate::PAYMENT,
        self::REFUND_TXN    => self::NA,
        self::REFUND_TXN_2  => self::NA,
        self::REFUND_TXN_3  => self::NA,
    ];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_TXN_STATE]) === false)
        {
            return null;
        }

        $txnType = $row[self::COLUMN_TXN_STATE];

        //
        // If txtType is anything other than what we have in
        // the list TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP, we want
        // the exception to be thrown, so now returning null in these cases.
        // (Earlier we used to return NA)
        //
        // Reason:
        // Sometimes we get extra comma in merchant_name and that causes the columns to
        // shift, thus we get txn_id in txt_state column. We want to catch these cases,
        // fix and re-upload the file for recon. Earlier we used to return NA when txtType
        // not in this list and that caused that row to get bypassed and such row did not
        // even get logged under recon_file_row, making it hard to debug.
        //

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$txnType] ?? null;
    }
}
