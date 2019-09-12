<?php

namespace RZP\Reconciliator\Hitachi\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_TRANSACTION_TYPE = 'transaction_type';

    const PURCHASE_TXN     = '00';
    const PURCHASE_TXN_BQR = '26';
    const REFUND_TXN       = '20';

    const TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN      => BaseReconciliate::PAYMENT,
        self::PURCHASE_TXN_BQR  => BaseReconciliate::PAYMENT,
        self::REFUND_TXN        => BaseReconciliate::REFUND
    ];

    const BLACKLISTED_COLUMNS = [
        PaymentReconciliate::COLUMN_MERCHANT_NAME,
    ];

    /**
     * Column Transaction Type in excel indicates whether
     * txn is payment or refund
     *
     * For payment, value is '0200'
     * For refund, value is '0220'
     *
     * @param array $row
     * @return string
     */
    protected function getReconciliationTypeForRow($row)
    {
        //
        // If the "transaction_type" column is not present
        // in the parsed row, not processing the row
        if (isset($row[self::COLUMN_TRANSACTION_TYPE]) === false)
        {
            return null;
        }

        $transactionType = $row[self::COLUMN_TRANSACTION_TYPE];

        return self::TRANSACTION_TYPE_TO_RECONCILIATION_TYPE_MAP[$transactionType] ?? self::NA;
    }
}
