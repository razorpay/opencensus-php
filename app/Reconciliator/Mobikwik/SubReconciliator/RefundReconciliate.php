<?php

namespace RZP\Reconciliator\Mobikwik;

use RZP\Reconciliator\Base;
use RZP\Models\Payment;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID     = 'OrderID';
    const COLUMN_REFUND_AMOUNT = 'RefundAmount';

    /**
     * Gets refund Id from row data
     *
     * @param $row array
     * @return $refundId string
     */
    protected function getRefundId($row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }

    protected function getPaymentId($row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntities = $this->repo->wallet_mobikwik->findSuccessfulRefundByRefundId(
                                                                $refundId,
                                                                Payment\Processor\Wallet::MOBIKWIK);

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $paymentId = $gatewayEntities->first()->getPaymentId();

        return $paymentId;
    }

    /**
     * Gets amount refunded.
     *
     * @param $row array
     * @return $refundAmount integer
     */
    protected function getRefundAmount($row)
    {
        $refundAmount = floatval($row[self::COLUMN_REFUND_AMOUNT]) * 100;

        return intval(number_format($refundAmount, 2, '.', ''));
    }
}
