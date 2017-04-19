<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    // ----- Row header names -----

    const REFUND_TXN_COLUMN     = 'refunded';

    const CAPTURE_TXN_COLUMN    = 'captured';

    /**
     * Goes through every row to determine
     * if the recon type is refund or payment
     *
     * @param $row array
     * @return string
     */
    protected function getReconciliationTypeForRow($row)
    {
        if ($row[self::REFUND_TXN_COLUMN] !== (float) 0)
        {
            return BaseReconciliate::REFUND;
        }

        return BaseReconciliate::PAYMENT;
    }
}
