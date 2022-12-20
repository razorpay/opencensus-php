<?php

namespace RZP\Reconciliator\UpiAirtel\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const COLUMN_REFUND             = 'refund';
    const COLUMN_PAYMENT            = 'misc cr';
    const COLUMN_ENTITY_TYPE        = 'transaction_status';
    const COLUMN_CUSTOMER_MOBILE_NO = 'customer_mobile_no';
    const COLUMN_NEW_RECON_TYPE     = 'transaction_type';
    const COLUMN_NEW_REFUND         = 'merchant_refund';
    const COLUMN_NEW_PAYMENT        = [
        'collect',
        'pay'
    ];

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_CUSTOMER_MOBILE_NO,
    ];

    protected function getReconciliationTypeForRow($row)
    {
        // recon type is fetched from new column in New MIS File format
       if (isset($row[self::COLUMN_NEW_RECON_TYPE]) === true)
       {
            if (in_array(strtolower($row[self::COLUMN_NEW_RECON_TYPE]),self::COLUMN_NEW_PAYMENT, true) === true)
            {
                return BaseReconciliate::PAYMENT;
            }
            else if (strtolower($row[self::COLUMN_NEW_RECON_TYPE]) === self::COLUMN_NEW_REFUND)
            {
                return BaseReconciliate::REFUND;
            }
            else
            {
                return self::NA;
            }
        }

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
        else
        {
            return self::NA;
        }
    }
}
