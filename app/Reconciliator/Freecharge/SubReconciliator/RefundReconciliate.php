<?php

namespace RZP\Reconciliator\Freecharge\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_ID     = 'transaction_id';
    const COLUMN_PAYMENT_ID    = 'order_id';
    const COLUMN_REFUND_AMOUNT = 'total_amount';
    // Refund id in MIS file is in the format
    // <merchant_id>_<refund_id>_<some number>. So we need to take the element at
    // index 1 after converting to an array.
    const REFUND_ID_INDEX = 1;

    protected function getRefundId(array $row)
    {
        $columnRefundId = $row[self::COLUMN_REFUND_ID];

        if (empty($columnRefundId) === true)
        {
            return null;
        }

        $columnRefundId = explode('_', $columnRefundId);

        $refundId = $columnRefundId[self::REFUND_ID_INDEX];

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        return $paymentId;
    }


    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = parent::getReconRefundAmount($row);

        return intval(number_format($refundAmount, 2, '.', ''));
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getBaseAmount() !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
