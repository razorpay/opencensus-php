<?php

namespace Reconciliator\HDFC;


use Reconciliator\Base;
use Reconciliator\Base\Reconciliate as BaseReconciliate;
use Reconciliator\Messenger;
use Trace\TraceCode;


class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = 'merchant_trackid';
    const COLUMN_CARD_TYPE   = 'debitcredit_type';
    const COLUMN_SERVICE_TAX = 'serv_tax';
    const COLUMN_SB_CESS     = 'sb_cess';
    const COLUMN_KK_CESS     = 'kk_cess';
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
        $paymentId = trim(str_replace("'", '', $paymentId));
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

            // HDFC reconciliation files have service tax and cess separately
            $serviceTax += $sbCess;
        }

        if (empty($row[self::COLUMN_KK_CESS]) === false)
        {
            // Convert kk cess into basic unit of currency. (ex: paise)
            $kkCess = floatval($row[self::COLUMN_KK_CESS]) * 100;

            // HDFC reconciliation files have service tax and cess separately
            $serviceTax += $kkCess;
        }

        return round($serviceTax);
    }


    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // HDFC reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
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