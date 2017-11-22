<?php

namespace RZP\Reconciliator\Mobikwik;

use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID    = 'OrderID';
    const COLUMN_REFUND_AMOUNT = 'RefundAmount';

    /**
     * Gets refund Id from row data
     *
     * Since only paymentId is provided, we fetch the refundId
     * from our database. Corresponding to the given paymentId
     *
     * @param $row array
     * @return $refundId string
     */
    protected function getRefundId($row)
    {
        $paymentId = $this->getPaymentId($row);

        if (empty($paymentId) === true)
        {
            return null;
        }

        $mobikwik = $this->app['repo']->mobikwik;

        // this is incorrect
        // todo : need to fix it to handle partial & failed refunds.
        $refundId = $mobikwik->findRefundByPaymentId($paymentId)->getRefundId();

        return $refundId;
    }

    /**
     * In mobikwik, we do not get a refund id.
     * The orderId column provided is a paymentId
     *
     * We use that to get the corresponding refund id
     *
     */
    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    /**
     * Gets amount refunded.
     *
     * @param $row array
     *
     * @return float|int|null $refundAmount
     */
    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = floatval($row[self::COLUMN_REFUND_AMOUNT]) * 100;

        return intval(number_format($refundAmount, 2, '.', ''));
    }
}
