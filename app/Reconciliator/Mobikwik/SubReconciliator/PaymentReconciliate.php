<?php

namespace RZP\Reconciliator\Mobikwik;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Messenger;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = 'OrderID';
    const COLUMN_SERVICE_TAX = 'ServiceTax';
    const COLUMN_FEE         = 'Fee';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];
        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        // Mobikwik gives something like 45.56738 as service tax
        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // Mobikwik reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
    }
}