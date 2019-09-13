<?php

namespace RZP\Reconciliator\Airtel\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_ENTITY_TYPE        = 'transaction_status';
    const COLUMN_PAYMENT            = 'Sale';
    const COLUMN_REFUND             = 'Refund';
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

        if ($row[self::COLUMN_ENTITY_TYPE] === self::COLUMN_PAYMENT)
        {
            return BaseReconciliate::PAYMENT;
        }
        else if ($row[self::COLUMN_ENTITY_TYPE] === self::COLUMN_REFUND)
        {
            return BaseReconciliate::REFUND;
        }
        else
        {
            return self::NA;
        }
    }
}
