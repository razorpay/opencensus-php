<?php

namespace RZP\Reconciliator\Getsimpl\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\Getsimpl\ReconFields;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_REFUND_ID          = ReconFields::PAY_ID;

    const COLUMN_REFUND_AMOUNT      = ReconFields::AMOUNT;

    protected function getRefundId($row)
    {
        if (isset($row[ReconFields::PAY_ID]) === false)
        {
            return null;
        }

        $refundId = $row[ReconFields::PAY_ID];

        return $refundId;
    }

    protected function getReconRefundAmount(array $row)
    {
        if (empty($row[ReconFields::AMOUNT]) === false)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconFields::AMOUNT]/100);
        }
    }

    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = $row[ReconFields::TYPE] ?? null;

        if ($rowStatus === 'REFUND')
        {
            return Payment\Refund\Status::PROCESSED;
        }

        return Payment\Refund\Status::FAILED;
    }
}
