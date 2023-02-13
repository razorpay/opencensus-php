<?php

namespace RZP\Reconciliator\IndusindDebitEmi\SubReconciliator;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{

    const BLACKLISTED_COLUMNS = [];

    public function getRefundId(array $row)
    {
        $refundId = $row[ReconciliationFields::EMI_ID] ?? null;

        return trim(str_replace("'", '', $refundId));
    }
}
