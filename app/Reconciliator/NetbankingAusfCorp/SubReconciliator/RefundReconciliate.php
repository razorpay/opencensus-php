<?php


use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Reconciliator\NetbankingAusfCorp\Constants;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate{

    protected function getRefundId(array $row)
    {
        $refundId = $row[Constants::PAYMENT_ID_EXT] ?? null;
        if($refundId !== null)
        {
            // Trimming the first char in file to clean up the data
            $refundId = substr($refundId, 1);
        }
        return $refundId;
    }

    protected function getReconRefundAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[Constants::REFUND_AMOUNT]);
    }

    protected function getGatewaySettledAt(array $row)
    {
        return $row[Constants::PAYMENT_DATE] ?? null;
    }
}
