<?php

namespace RZP\Reconciliator\PayZappV2\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate;

class CombinedReconciliate extends Base\SubReconciliator\CombinedReconciliate
{
    const BLACKLISTED_COLUMNS = [];

    /*******************
     * Row Header Names
     *******************/
    const COLUMN_ENTITY_TYPE      = 'rec_fmt';
    const COLUMN_UPI_ENTITY_TYPE  = 'trans_type';

    protected function getReconciliationTypeForRow($row): string
    {
        $txnType    = $row[self::COLUMN_ENTITY_TYPE] ?? null;
        $upiTxnType = $row[self::COLUMN_UPI_ENTITY_TYPE] ?? null;

        if ($txnType === 'BAT')
        {
            return Reconciliate::PAYMENT;
        }
        else if ($txnType === 'CVD')
        {
            return Reconciliate::REFUND;
        }
        else if ($upiTxnType === 'PAY')
        {
            return Reconciliate::PAYMENT;
        }
        else if ($upiTxnType === 'CREDIT')
        {
            return Reconciliate::REFUND;
        }
        else
        {
            return self::NA;
        }
    }
}
