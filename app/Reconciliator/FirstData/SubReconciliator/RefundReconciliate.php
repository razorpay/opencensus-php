<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\RefundReconciliate
{
    /***************************************
     * Row Header Names
     *
     * ft_no maps to gateway_transaction_id
     ***************************************/
    const COLUMN_GATEWAY_PAYMENT_ID = 'ft_no';
    const COLUMN_REFUND_AMOUNT      = 'transaction_amt';
    const COLUMN_ARN                = 'arn_no';

    /**
     * Gets refund Id from gateway entity
     * using helper function.
     *
     * @param $row array
     * @return $refundId string
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
     * @param $row        array
     * @return $paymentId string
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
     * @param $row           array
     * @return $refundAmount integer
     */
    protected function getRefundAmount(array $row)
    {
        $refundAmount = parent::getRefundAmount($row);

        $refundAmount = intval(number_format($refundAmount, 2, '.', ''));

        $refundAmount = abs($refundAmount);

        return $refundAmount;
    }

    /**
     * Helper function to return FirstData gateway entity
     * This will be used by getRefundId & getPaymentId
     *
     * The payment id in the file is under column 'SESSION ID ASPD'.
     * However, the id is capitalized,
     * whereas the ids in our database are case sensitive.
     * Hence this column is rendered useless.
     * So we are using gateway transaction id to retrieve payment.
     *
     * @param  $row array
     * @return $payment FirstData\Entity
     */
    protected function getGatewayPayment($row)
    {
        $gatewayTxnIdVal = $row[COLUMN_GATEWAY_PAYMENT_ID];

        // The val of column 'FT NO' has a lot of leading zeros,
        // which need to be removed in order to get actual gateway txn id.
        $gatewayTxnId = ltrim($gatewayTxnIdVal, '0');

        // The broad assumption here is that these ids are unique
        // for every transaction.
        $payment = $this->app['repo']->first_data
                                     ->findByGatewayTransactionIdAndAction(
                                       $gatewayTxnId, Action::REFUND);

        return $payment;
    }

    /**
     * Fetches ARN for given rows
     *
     * @param $row array
     * @return $arn string
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
     * @param $arn           string
     * @param $gatewayRefund PublicEntity
     * @return void
     */
    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setArn($arn);
    }
}
