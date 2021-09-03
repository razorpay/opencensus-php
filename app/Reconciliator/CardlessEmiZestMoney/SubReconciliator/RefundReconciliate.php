<?php

namespace RZP\Reconciliator\CardlessEmiZestMoney\SubReconciliator;

use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Reconciliator\CardlessEmiZestMoney\Reconciliate;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    protected function getRefundId(array $row)
    {
        return $row[Reconciliate::REFUND_ID] ?? null;
    }

    protected function getReconRefundAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[Reconciliate::REFUND_AMOUNT]);
    }

}
