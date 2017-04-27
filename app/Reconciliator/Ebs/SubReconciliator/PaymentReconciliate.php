<?php

namespace RZP\Reconciliator\Ebs;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    // ----- Row header names -----
    const COLUMN_FEE                = 'tdr';
    const COLUMN_SERVICE_TAX        = 'service_tax';
    const COLUMN_PAYMENT_ID         = 'merchant_ref_no';
    const COLUMN_BANK_REFERENCE_NO  = 'bank_reference';

    const COLUMN_PAYMENT_AMOUNT     = 'captured';           // website
    const COLUMN_CREDIT_AMOUNT      = 'credit';             // email

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
     * Gets amount captured.
     *
     * Since we get two type of sheets,
     * both have different column headers for payment amount.
     * We need to check which one of them is set
     * and get the payment amount accordingly.
     *
     * @param $row array
     * @return $paymentAmount integer
     */
    protected function getGatewayPaymentAmount($row)
    {
        if (isset($row[self::COLUMN_PAYMENT_AMOUNT]) === true)
        {
            $paymentColumnVal = $row[self::COLUMN_PAYMENT_AMOUNT];
        }

        elseif (isset($row[self::COLUMN_CREDIT_AMOUNT]) === true)
        {
            $paymentColumnVal = $row[self::COLUMN_CREDIT_AMOUNT];
        }

        $paymentAmount = floatval($paymentColumnVal) * 100;

        return abs($paymentAmount);
    }

    /**
     * Gets service tax levied by EBS
     *
     * @param $row array
     * @return $serviceTax float
     */
    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        // todo : check for KKC, other taxes
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        return abs(round($serviceTax));
    }

    /**
     * Gets TDR
     *
     * @param $row array
     * @return $fee float
     */
    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return abs(round($fee));

    }

    protected function getReferenceNumber($row)
    {
        $referenceNumber = $row[self::COLUMN_BANK_REFERENCE_NO];

        return $referenceNumber;
    }
}
