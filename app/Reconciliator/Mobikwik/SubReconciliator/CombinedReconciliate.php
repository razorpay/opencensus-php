<?php

namespace RZP\Reconciliator\Mobikwik;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_AMOUNT    = 'refundamount';

    /**
     * There is no column defining whether the row is refund or payment
     * Hence we check the value of 'COLUMN_REFUND_AMOUNT'
     *
     * @param $row array
     */
    protected function getReconciliationTypeForRow($row)
    {
        // disabling refund recon for mobikwik
        // because we have to find a way to handle partial refund
        // and failed refunds.
        // The information given in mobikwik recon file is not sufficient enough
        if ($row[self::COLUMN_REFUND_AMOUNT] !== 'None')
        {
            return self::NA;
        }

        return BaseReconciliate::PAYMENT;
    }
}
