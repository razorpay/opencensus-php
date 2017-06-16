<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /******************************************************
     * Row Header Names
     *
     * ft_no maps to gateway_transaction_id
     * comm_amount (commission amount) maps to gateway fee
     ******************************************************/
    const COLUMN_GATEWAY_PAYMENT_ID = 'ft_no';
    const COLUMN_GATEWAY_FEE        = 'comm_amount';
    const COLUMN_PAYMENT_AMOUNT     = 'transaction_amt';

    /**
     * The payment id in the file is under column 'SESSION ID ASPD'.
     * However, the id is capitalized,
     * whereas the ids in our database are case sensitive.
     * Hence this column is rendered useless.
     * So we are using gateway transaction id to retrieve payment id.
     *
     * @param $row        array
     * @return $paymentId string
     */
    protected function getPaymentId($row)
    {
        $gatewayTxnIdVal = $row[COLUMN_GATEWAY_PAYMENT_ID];

        // The val of column 'FT NO' has a lot of leading zeros,
        // which need to be removed in order to get actual gateway txn id.
        $gatewayTxnId = ltrim($gatewayTxnIdVal, '0');

        // The broad assumption here is that these ids are unique
        // for every transaction.
        $paymentId = $this->app['repo']->first_data
                                       ->findByGatewayTransactionIdAndAction(
                                         $gatewayTxnId, Action::PURCHASE)
                                       ->getPaymentId();

        return $paymentId;
    }

    /**
     * Gets amount captured.
     *
     * We are converting to int after casting to string as PHP randomly
     * returns wrong int values due to differing floating point precisions
     * So something like intval(31946.0) may give 31945 or 31946.
     * Convering to string using number_format and then converting
     * is a hack to avoid this issue
     *
     * @param $row            array
     * @return $paymentAmount integer
     */
    protected function getGatewayPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    /**
     * Gateway Fee is given as commission amount.
     * Seems like a consolidated amount, can be returned as is.
     * We get a value like 11.35 for this column.
     *
     * Usually we add service tax to this value,
     * but ST for first data is 0, hence, no addition.
     *
     * @param $row         array
     * @return $gatewayFee integer
     */
    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $gatewayFee = floatval($row[self::COLUMN_GATEWAY_FEE]) * 100;

        return round($gatewayFee);
    }

    /**
     * There is no service tax for first data,
     * it is considered as zero
     *
     * @param  $row array
     * @return 0
     */
    protected function getGatewayServiceTax($row)
    {
        return 0;
    }
}
