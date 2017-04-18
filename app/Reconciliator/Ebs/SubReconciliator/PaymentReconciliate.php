<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    // ----- Row header names -----
    const COLUMN_PAYMENT_ID         = 'merchant_ref_no';
    const COLUMN_PAYMENT_AMOUNT     = 'captured';
    const COLUMN_PAYMENT_DATE       = 'cap_date';
    const COLUMN_GATEWAY_PAYMENT_ID = 'paymentid';

    /**
     * Gets payment_id from row data
     *
     * @param $row array
     * @return $paymentId string
     */
    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    /**
     * Gets amount captured
     *
     * @param $row array
     * @return $paymentAmount integer
     */
    protected function getGatewayPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        return intval(number_format($paymentAmount, 2, '.', ''));
    }
}

