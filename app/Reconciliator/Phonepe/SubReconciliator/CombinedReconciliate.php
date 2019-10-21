<?php

namespace RZP\Reconciliator\Phonepe\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\WalletPhonepe\ReconFields;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[ReconFields::PAYMENT_TYPE]) === false)
        {
            return null;
        }

        switch ($row[ReconFields::PAYMENT_TYPE])
        {
            case ReconFields::PAYMENT:
                return BaseReconciliate::PAYMENT;

            case ReconFields::REFUND:
                return BaseReconciliate::REFUND;

            default:
                return self::NA;
        }
    }
}