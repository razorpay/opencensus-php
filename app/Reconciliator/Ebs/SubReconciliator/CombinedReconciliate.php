<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class CombinedReconciliate extends Base\CombinedReconciliate
{
    // ----- Row header names -----

    const REFUND_TXN_COLUMN     = 'Refunded';

    const CAPTURE_TXN_COLUMN    = 'Captured';

    protected function getReconciliationTypeForRow($row)
    {
        if ($row[self::REFUND_TXN_COLUMN] !== 0)
        {
            return BaseReconciliate::REFUND;
        }

        return BaseReconciliate::PAYMENT;
    }
}
