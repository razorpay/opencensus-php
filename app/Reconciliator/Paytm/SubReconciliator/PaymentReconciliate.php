<?php

namespace RZP\Reconciliator\Paytm;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Messenger;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID             = 'order_id';
    const COLUMN_SERVICE_TAX            = 's_tax';
    const COLUMN_FEE                    = 'rev_commm';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into basic unit of currency. (ex: paise)
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return round($fee);
    }
}