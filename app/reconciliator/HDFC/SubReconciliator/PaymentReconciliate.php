<?php

namespace Reconciliator\HDFC\SubReconciliator;


use Models\Card\IIN;

use Models\Payment;
use Gateway\AxisMigs;

use Reconciliator\Base\SubReconciliator;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
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
        $this->paymentRepo = new Payment\Repository;
        $this->gatewayRepo = new AxisMigs\Repository;
        $this->iinRepo     = new IIN\Repository;
    }


    protected function getRowDetailsStructured($row)
    {
        /* GET PAYMENT ID DETAILS */
        $paymentId = $row[self::ROW_PAYMENT_ID];
        $paymentId = str_replace("'", '', $paymentId);

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        $this->payment = $this->paymentRepo->findOrFail($paymentId);

        /* GET CARD TYPE DETAILS */
        if (isset($row[self::ROW_CARD_TYPE]) === true)
        {
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