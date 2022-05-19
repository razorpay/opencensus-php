<?php

namespace RZP\Reconciliator\NetbankingDbs\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Refund;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\NetbankingDbs\Reconciliate;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    protected function getRefundId(array $row)
    {
        return $row[Reconciliate::REFUND_ID] ?? null;
    }

    protected function getReconRefundAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::TRANSACTION_AMOUNT]);
    }

    protected function getArn(array $row)
    {
        return $row[Reconciliate::BANK_REF_NO] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $status = $row[Reconciliate::TRANSACTION_STATUS];

        if ($status === Reconciliate::TRANSACTION_SUCCESS)
        {
            return Refund\Status::PROCESSED;
        }
        else
        {
            return Refund\Status::FAILED;
        }
    }
}
