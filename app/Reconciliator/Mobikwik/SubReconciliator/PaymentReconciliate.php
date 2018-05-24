<?php

namespace RZP\Reconciliator\Mobikwik;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID     = 'orderid';
    const COLUMN_SERVICE_TAX    = 'servicetax';
    const COLUMN_FEE            = 'fee';
    const COLUMN_PAYMENT_AMOUNT = 'txnamount';

    /**
     * Gets payment_id from row data
     *
     * @param array $row
     * @return string|null
     */
    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        return $paymentId;
    }

    /**
     * Gets amount captured.
     *
     * We are converting to int after casting to string as PHP randomly
     * returns wrong int values due to differing floating point precisions
     * So something like intval(31946.0) may give 31945 or 31946
     * Convering to string using number_format and then converting
     * is a hack to avoid this issue
     *
     * @param $row array
     *
     * @return int $paymentAmount
     */
    protected function getReconPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    /**
     * Mobikwik gives something like 45.56738 as service tax
     *
     * @param  $row array
     * @return $serviceTax float
     */
    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        return round($serviceTax);
    }

    /**
     * Mobikwik reconciliation files have fee and service tax separately
     * Round off because service tax is like 5.56731
     *
     * @param  $row array
     * @return $fee float
     */
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
