<?php

namespace Reconciliator\Axis\SubReconciliator;


use Models\Card\IIN;

use Models\Payment;
use Gateway\AxisMigs;

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
        $this->paymentRepo = new Payment\Repository;
        $this->gatewayRepo = new AxisMigs\Repository;
        $this->iinRepo     = new IIN\Repository;
    }


    protected function getRowDetailsStructured($row)
    {
        /* GET PAYMENT ID DETAILS */
        $paymentId = $row[self::ROW_PAYMENT_ID];

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        // TODO: If payment not found, return null. Raise an alert too.
        $this->payment = $this->paymentRepo->findOrFail($paymentId);

        // TODO: Abstract this out into a method. Along with others.
        /* GET CARD TYPE DETAILS */
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

        /* GET SERVICE TAX DETAILS */
        $serviceTax = $row[self::ROW_SERVICE_TAX];

        /* ASSIGN VALUES TO RETURN */
        $rowDetails = [
            self::PAYMENT_ID  => $paymentId,
            self::CARD_TYPE   => $cardType,
            self::SERVICE_TAX => $serviceTax,
        ];

        return $rowDetails;
    }
}