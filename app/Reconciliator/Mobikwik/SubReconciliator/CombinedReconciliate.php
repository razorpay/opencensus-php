<?php

namespace RZP\Reconciliator\Mobikwik;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_DATE  =  'refundadjusteddate';

    const VALUE_REFUND_DATE   = 'None';

    /**
     * There is no column defining whether the row is refund or payment
     * Hence we check the value of 'COLUMN_REFUND_AMOUNT'
     *
     * @param $row array
     *
     * @return null|string
     */
    protected function getReconciliationTypeForRow($row)
    {
        if (isset($row[self::COLUMN_REFUND_DATE]) === false)
        {
            return null;
        }

        //
        // Disabling refund recon for mobikwik because we have to
        // find a way to handle partial refund and failed refunds.
        // The information given in mobikwik recon file is not sufficient enough.
        //
        if ($row[self::COLUMN_REFUND_DATE] !== self::VALUE_REFUND_DATE)
        {
            return self::NA;
        }

        return BaseReconciliate::PAYMENT;
    }
}
