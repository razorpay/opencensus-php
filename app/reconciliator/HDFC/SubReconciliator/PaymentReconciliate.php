<?php

namespace Reconciliator\HDFC\SubReconciliator;


use Models\Card\IIN;

use Models\Payment;
use Gateway\AxisMigs;

use Reconciliator\Base;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    const CREDIT = 'credit';
    const DEBIT = 'debit';


    /*******************
     * Row Header Names
     *******************/

    const ROW_PAYMENT_ID  = 'merchant_trackid';
    const ROW_CARD_TYPE   = 'debitcredit_type';
    const ROW_SERVICE_TAX = 'serv_tax';


    /*******************
     * Instance objects
     *******************/

    protected $paymentRepo;
    protected $gatewayRepo;
    protected $iinRepo;


    /*********************
     * Instance variables
     *********************/

    protected $payment;

    public function __construct()
    {
        // These are being used by the parent classes.
        $this->gatewayRepo = new AxisMigs\Repository;
        $this->paymentRepo = new Payment\Repository;
        $this->iinRepo     = new IIN\Repository;
    }
    
    
    protected function getServiceTax($row)
    {
        $serviceTax = $row[self::ROW_SERVICE_TAX];
        
        return $serviceTax;
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
            $cardType = self::CREDIT;
        }
        else if ($cardType === 'dd')
        {
            $cardType = self::DEBIT;
        }
        else
        {
            // TODO: Raise an alert for card type being present in the row
            // but the value is not what was expected.
        }

        return $cardType;
    }


    protected function getPaymentId($row)
    {
        $paymentId = $row[self::ROW_PAYMENT_ID];
        $paymentId = str_replace("'", '', $paymentId);

        return $paymentId;
    }
}