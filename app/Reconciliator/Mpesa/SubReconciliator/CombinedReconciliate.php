<?php

namespace RZP\Reconciliator\Mpesa\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_TRANSACTION_TYPE   = 'service_name';
    const COLUMN_PAYMENT            = 'Online Payment';
    const COLUMN_REFUND             = 'Txn. ID based reversals';

    const COLUMN_SENDER_NAME        = 'sender_name';
    const COLUMN_SENDER_MOBILE_NO   = 'sender_mobile_no';

    const BLACKLISTED_COLUMNS       = [
        self::COLUMN_SENDER_NAME,
        self::COLUMN_SENDER_MOBILE_NO,
    ];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_TRANSACTION_TYPE]) === false)
        {
            return null;
        }

        if ($row[self::COLUMN_TRANSACTION_TYPE] === self::COLUMN_PAYMENT)
        {
            return BaseReconciliate::PAYMENT;
        }
        else if ($row[self::COLUMN_TRANSACTION_TYPE] === self::COLUMN_REFUND)
        {
            //TODO return refund when we get refund identifier in the file
            return self::NA;
        }
        else
        {
            return self::NA;
        }
    }
}
