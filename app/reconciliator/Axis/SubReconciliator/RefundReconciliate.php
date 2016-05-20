<?php

namespace Reconciliator\Axis\SubReconciliator;


use Models\Payment;
use Gateway\AxisMigs;
use Models\Card\IIN;

use Reconciliator\Base\SubReconciliator;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const CREDIT = 'credit';
    const DEBIT = 'debit';


    /*******************
     * Row Header Names
     *******************/

    const ROW_PAYMENT_ID  = 'merchant_trans_ref';
    const ROW_CARD_TYPE   = 'card_type';
    const ROW_SERVICE_TAX = 'service_tax145';


    /*******************
     * Instance objects
     *******************/

    protected $gatewayRepo;
    protected $paymentRepo;
    protected $iinRepo;


    /*********************
     * Instance variables
     *********************/

    protected $payment;

    public function __construct()
    {
        // These are being used by the parent classes.
        $this->paymentRepo = new Payment\Repository;
        $this->iinRepo     = new IIN\Repository;
        $this->gatewayRepo = new AxisMigs\Repository;
    }


    protected function getPaymentId($row)
    {
        $paymentId = $row[self::ROW_PAYMENT_ID];

        return $paymentId;
    }


    protected function getServiceTax($row)
    {
        $serviceTax = $row[self::ROW_SERVICE_TAX];

        return $serviceTax;
    }


    protected function getCardType($row)
    {
        if (isset($row[self::ROW_CARD_TYPE]) === true)
        {
            $cardType = strtolower($row[self::ROW_CARD_TYPE]);

            if ($cardType === 'c')
            {
                $cardType = self::CREDIT;
            }
            else if ($cardType === 'd')
            {
                $cardType = self::DEBIT;
            }
            else
            {
                // TODO: Raise an alert for card type being present in the row
                // but the value is not what was expected.
            }
        }
        else
        {
            $cardType = null;
        }

        return $cardType;
    }
}