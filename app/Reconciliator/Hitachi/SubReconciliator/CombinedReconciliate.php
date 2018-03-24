<?php

namespace RZP\Reconciliator\Hitachi;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_MESSAGE_TYPE = 'message_type';

    const PURCHASE_TXN    = '0200';
    const REFUND_TXN      = '0220';

    const MESSAGE_TYPE_TO_RECONCILIATION_TYPE_MAP = [
        self::PURCHASE_TXN => BaseReconciliate::PAYMENT,
        self::REFUND_TXN   => BaseReconciliate::REFUND
    ];

    /**
     * Column Message Type in excel indicates whether
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
        // If the "message_type" column is not present
        // in the parsed row, not processing the row
        if (isset($row[self::COLUMN_MESSAGE_TYPE]) === false)
        {
            return null;
        }

        $messageType = $row[self::COLUMN_MESSAGE_TYPE];

        return self::MESSAGE_TYPE_TO_RECONCILIATION_TYPE_MAP[$messageType] ?? self::NA;
    }
}
