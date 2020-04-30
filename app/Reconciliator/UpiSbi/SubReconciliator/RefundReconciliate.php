<?php

namespace RZP\Reconciliator\UpiSbi\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const BANK_REMARK             = 'bankremark';
    const COLUMN_REFUND_ID        = 'refreqno';
    const COLUMN_REFUND_AMOUNT    = 'refundreqamt';

    const SUCCESS = 'refund success';

    const BLACKLISTED_COLUMNS = [];

    protected function getRefundId(array $row)
    {
        return $row[self::COLUMN_REFUND_ID] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = $row[self::BANK_REMARK] ?? null;

        if (strtolower($rowStatus) === self::SUCCESS)
        {
            return Payment\Refund\Status::PROCESSED;
        }

        return Payment\Refund\Status::FAILED;
    }
}
