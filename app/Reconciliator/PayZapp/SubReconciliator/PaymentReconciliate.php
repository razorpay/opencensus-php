<?php

namespace RZP\Reconciliator\PayZapp;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Reconciliator\Messenger;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = 'TRACK ID';
    const COLUMN_CARD_TYPE   = 'CREDIT/DEBIT CARD FLAG';
    const COLUMN_SERVICE_TAX = 'SERVICE TAX';
    const COLUMN_SB_CESS     = 'SWACH BHARAT CESS';
    const COLUMN_KK_CESS     = 'KRISHI KALYAN CESS';
    const COLUMN_EDU_CESS    = 'EDUCESS';
    const COLUMN_FEE         = 'COMMISSION AMT';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];
        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) * 100;

        if (empty($row[self::COLUMN_SB_CESS]) === false)
        {
            // Convert sb cess into basic unit of currency. (ex: paise)
            $sbCess = floatval($row[self::COLUMN_SB_CESS]) * 100;

            // PayZapp reconciliation files have service tax and cess separately
            $serviceTax += $sbCess;
        }

        if (empty($row[self::COLUMN_KK_CESS]) === false)
        {
            // Convert kk cess into basic unit of currency. (ex: paise)
            $kkCess = floatval($row[self::COLUMN_KK_CESS]) * 100;

            // PayZapp reconciliation files have service tax and cess separately
            $serviceTax += $kkCess;
        }

        if (empty($row[self::COLUMN_EDU_CESS]) === false)
        {
            // Convert edu cess into basic unit of currency. (ex: paise)
            $eduCess = floatval($row[self::COLUMN_EDU_CESS]) * 100;

            // PayZapp reconciliation files have service tax and cess separately
            $serviceTax += $eduCess;
        }

        return round($serviceTax);
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // PayZapp reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
    }
}