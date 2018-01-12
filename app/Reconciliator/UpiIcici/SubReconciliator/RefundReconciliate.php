<?php

namespace RZP\Reconciliator\UpiIcici;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    const REFUND_ID            = 'merchanttranid';
    const COLUMN_REFUND_AMOUNT = 'refund_amount';
    const ORIGINAL_BANK_RRN    = 'original_bank_rrn';
    const REFUND_TRANS_DATE    = 'refund_transaction_date';
    const REFUND_TRANS_TIME    = 'refund_transaction_time';

    protected function getRefundId(array $row)
    {
        return $row[self::REFUND_ID] ?? null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $refundDate = $row[self::REFUND_TRANS_DATE] ?? null;

        $refundTime = $row[self::REFUND_TRANS_TIME] ?? null;

        if ((empty($refundDate) === true) or
            (empty($refundTime) === true))
        {
            return null;
        }

        $refundSettledAt = $refundDate . ' ' . $refundTime;

        return Carbon::createFromFormat('d-m-Y h:i a', $refundSettledAt, Timezone::IST)->getTimestamp();
    }

    protected function getPaymentId(array $row)
    {
        $rrn = $row[self::ORIGINAL_BANK_RRN] ?? null;

        if (empty($rrn) == true)
        {
            return null;
        }

        $upiEntity = $this->repo->upi->fetchByGatewayPaymentIdAndAction($rrn);

        return $upiEntity->getPaymentId();
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $reconAmount = $this->getReconRefundAmount($row);

        $refundAmount = $this->refund->getBaseAmount();

        if ($reconAmount !== $refundAmount)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->refund->getBaseAmount(),
                    'currency'        => $this->refund->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        return get_integer_formatted_amount($row[self::COLUMN_REFUND_AMOUNT]);
    }
}
