<?php

namespace RZP\Reconciliator\Paytm;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Messenger;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID          = 'ORDER ID';
    const COLUMN_TRANSACTION_AMOUNT  = 'TXN_AMOUNT';
    const COLUMN_SETTLED_AMOUNT      = 'SETTLED AMOUNT';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        // Paytm recon files do not contain service tax
        return 0;
    }

    protected function getGatewayFee($row)
    {
        $fee = $row[self::COLUMN_TRANSACTION_AMOUNT] - $row[self::COLUMN_SETTLED_AMOUNT];

        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($fee) * 100;

        return round($fee);
    }
}