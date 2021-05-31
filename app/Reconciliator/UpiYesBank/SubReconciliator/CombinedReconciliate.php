<?php

namespace RZP\Reconciliator\UpiYesBank\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_REFUND             = 'debit';
    const COLUMN_PAYMENT            = 'credit';
    const COLUMN_ENTITY_TYPE        = 'drcr';
    const COLUMN_CUSTOMER_MOBILE_NO = 'customer_mobile_no';

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_CUSTOMER_MOBILE_NO,
    ];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_ENTITY_TYPE]) === false)
        {
            return null;
        }

        if (strtolower($row[self::COLUMN_ENTITY_TYPE]) === self::COLUMN_PAYMENT)
        {
            return BaseReconciliate::PAYMENT;
        }
        else if (strtolower($row[self::COLUMN_ENTITY_TYPE]) === self::COLUMN_REFUND)
        {
            return BaseReconciliate::REFUND;
        }
        return self::NA;
    }
}
