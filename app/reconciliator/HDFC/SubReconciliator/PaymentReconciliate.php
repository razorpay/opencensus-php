<?php

namespace Reconciliator\HDFC\SubReconciliator;


use Reconciliator\Base\SubReconciliator;
use Reconciliator\Base\Reconciliate as BaseReconciliate;
use Reconciliator\Messenger;
use Trace\TraceCode;


class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = 'merchant_trackid';
    const COLUMN_CARD_TYPE   = 'debitcredit_type';
    const COLUMN_SERVICE_TAX = 'serv_tax';
    const COLUMN_SB_CESS     = 'sb_cess';
    const COLUMN_FEE         = 'msf';

    protected $messenger;


    public function __construct()
    {
        $this->messenger = new Messenger();
        parent::__construct();
    }


    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];
        $paymentId = str_replace("'", '', $paymentId);

        return $paymentId;
    }


    protected function getServiceTax($row)
    {
        // HDFC reconciliation files have service tax and sb cess fields separately
        $serviceTax = floatval($row[self::COLUMN_SERVICE_TAX]) + floatval($row[self::COLUMN_SB_CESS]);

        return $serviceTax;
    }


    protected function getFee($row)
    {
        $fee = $row[self::COLUMN_FEE];

        return floatval($fee);
    }


    protected function getCardType($row)
    {
        if (isset($row[self::COLUMN_CARD_TYPE]) === false)
        {
            return null;
        }

        $cardType = strtolower($row[self::COLUMN_CARD_TYPE]);

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
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => 'Unable to figure out the card type.',
                    'recon_card_type' => $cardType,
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            // It's as good as no card type present in the row.
            return null;
        }

        return $cardType;
    }
}