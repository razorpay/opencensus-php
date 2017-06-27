<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\RefundReconciliate
{
    /******************
     * Row Header Names
     ******************/

    // session_id_aspd maps to caps_payment_id
    const COLUMN_CAPS_PAYMENT_ID = 'session_id_aspd';
    const COLUMN_REFUND_AMOUNT   = 'transaction_amt';
    const COLUMN_ARN             = 'arn_no';

    /**
     * Gets refund Id from gateway entity
     * using helper function.
     *
     * @param array   $row
     * @return string $refundId
     */
    protected function getRefundId($row)
    {
        $refundId = $this->getGatewayPayment($row)->getRefundId();

        return $refundId;
    }

    /**
     * Gets payment id from gateway entity
     * using helper function.
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId($row)
    {
        $paymentId = $this->getGatewayPayment($row)->getPaymentId();

        return $paymentId;
    }

    /**
     * Fetches refund amount from the file.
     * It is negative for refunds, so we will be taking abs value
     *
     * @param array    $row
     * @return integer $refundAmount
     */
    protected function getRefundAmount(array $row)
    {
        $refundAmount = parent::getRefundAmount($row);

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue

        $refundAmount = intval(number_format($refundAmount, 2, '.', ''));

        $refundAmount = abs($refundAmount);

        return $refundAmount;
    }

    /**
     * Helper function to return FirstData gateway entity
     * This will be used by getRefundId & getPaymentId
     *
     * The payment id in the file is under column 'SESSION ID ASPD'.
     * However, the id is sometimes capitalized, sometimes not,
     * whereas the ids in our database are case sensitive.
     * So we compare ('SESSION ID ASPD') to 'caps_payment_id' of FirstData
     *
     * @param array             $row
     * @return FirstData\Entity $payment
     */
    protected function getGatewayPayment($row)
    {
        $capsPaymentId = $row[self::COLUMN_CAPS_PAYMENT_ID];

        //
        // doing this because sometimes the session_id (payment_id) is all caps,
        // sometimes it's not. Since, we are searching with caps_payment_id in
        // our DB, we capitalize the session_id always, to ensure we always get
        // caps_payment_id.
        //
        $capsPaymentId = strtoupper($capsPaymentId);

        //
        // The broad assumption here is that these ids will not collide
        // The mathematical probability is very low (not zero though)!
        //
        $payment = $this->app['repo']->first_data
                                     ->findByCapsPaymentIdAndAction(
                                       $capsPaymentId, Action::REFUND);

        return $payment;
    }

    /**
     * Fetches ARN for given rows
     *
     * @param array   $row
     * @return string $arn
     */
    protected function getArn(array $row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            return null;
        }

        $arn = $row[self::COLUMN_ARN];

        return $arn;
    }

    /**
     * Sets ARN in gateway entity
     *
     * @param string       $arn
     * @param PublicEntity $gatewayRefund
     * @return void
     */
    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setArn($arn);
    }

    /**
     * Checks if refund amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getAmount() !== $this->getRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Refund amount mismatch',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            return false;
        }

        return true;
    }
}
