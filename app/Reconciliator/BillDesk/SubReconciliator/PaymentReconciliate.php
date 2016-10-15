<?php

namespace RZP\Reconciliator\BillDesk;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Messenger;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = 'Ref. 1';
    const COLUMN_SERVICE_TAX = 'S Tax (Rs Ps)';
    const COLUMN_FEE         = 'Charges (Rs.Ps)';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into basic unit of currency (ex: paise)
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // BillDesk reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
    }
}