<?php

namespace Reconciliator\HDFC\SubReconciliator;


use Reconciliator\Base\SubReconciliator;
use Reconciliator\Base\Reconciliate as BaseReconciliate;


class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const ROW_PAYMENT_ID  = 'merchant_trackid';
    const ROW_CARD_TYPE   = 'debitcredit_type';
    const ROW_SERVICE_TAX = 'serv_tax';
    const ROW_SB_CESS     = 'sb_cess';
    const ROW_FEE         = 'msf';
    
    
    public function __construct()
    {
        parent::__construct();
    }


    protected function getPaymentId($row)
    {
        $paymentId = $row[self::ROW_PAYMENT_ID];
        $paymentId = str_replace("'", '', $paymentId);

        return $paymentId;
    }
    

    protected function getServiceTax($row)
    {
        // HDFC reconciliation files have service tax and sb cess fields separately
        $serviceTax = floatval($row[self::ROW_SERVICE_TAX]) + floatval($row[self::ROW_SB_CESS]);

        return $serviceTax;
    }


    protected function getFee($row)
    {
        $fee = $row[self::ROW_FEE];

        return floatval($fee);
    }


    protected function getCardType($row)
    {
        if (isset($row[self::ROW_CARD_TYPE]) === false)
        {
            return null;
        }

        $cardType = strtolower($row[self::ROW_CARD_TYPE]);

        if ($cardType === 'dc')
        {
            $cardType = BaseReconciliate::CREDIT;
        }
        else if ($cardType === 'dd')
        {
            $cardType = BaseReconciliate::DEBIT;
        }
        else
        {
            // TODO: Raise an alert for card type being present in the row
            // but the value is not what was expected.
        }

        return $cardType;
    }
}