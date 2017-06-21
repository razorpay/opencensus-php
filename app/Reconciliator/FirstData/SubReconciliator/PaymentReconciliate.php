<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     ******************/

    // session_id_aspd maps to caps_payment_id
    // comm_amount (commission amount) maps to gateway fee
    const COLUMN_CAPS_PAYMENT_ID = 'session_id_aspd';
    const COLUMN_GATEWAY_FEE     = 'comm_amount';
    const COLUMN_PAYMENT_AMOUNT  = 'transaction_amt';

    const INTERNATIONAL_CARD_REGEX = 'international';

    /**
     * The payment id in the file is under column 'SESSION ID ASPD'.
     * However, the id is sometimes capitalized, somethings not,
     * whereas the ids in our database are case sensitive.
     * So we compare ('SESSION ID ASPD') to 'caps_payment_id' of FirstData
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId($row)
    {
        $capsPaymentId = $row[self::COLUMN_GATEWAY_PAYMENT_ID];

        // doing this because sometimes the ids are all caps, sometimes not
        // the caps_payment_id in database is however all caps! :D
        // just to be on the safer side.
        $capsPaymentId = strtoupper($capsPaymentId);

        // The broad assumption here is that these ids will not collide
        // The mathematical probility is very low (not zero though)!
        $payment = $this->app['repo']->first_data
                                     ->findByCapsPaymentIdAndAction(
                                       $capsPaymentId, Action::PURCHASE)
                                     -> getPaymentId();

        return $paymentId;
    }

    /**
     * Gets amount captured.
     *
     * @param array $row
     * @return integer $paymentAmount
     */
    protected function getGatewayPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Convering to string using number_format and then converting
        // is a hack to avoid this issue
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
     * @param array    $row
     * @return integer $gatewayFee
     */
    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $gatewayFee = floatval($row[self::COLUMN_GATEWAY_FEE]) * 100;

        return round($gatewayFee);
    }

    /**
     * We don't get service tax for First Data in the MIS files,
     * it is considered as zero
     *
     * @param  array row
     * @return 0
     */
    protected function getGatewayServiceTax($row)
    {
        return 0;
    }

    /**
     * Checks if payment amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return void
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getAmount() !== $this->getGatewayPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Payment amount mismatch',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    protected function getCardDetails($row)
    {
    }
}
