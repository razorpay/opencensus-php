<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Exception\ReconciliationException;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Models\Bank\IFSC;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID  = 'merchant_trackid';
    const COLUMN_CARD_TYPE   = 'debitcredit_type';
    const COLUMN_SERVICE_TAX = ['serv_tax', 'service_tax', 'st_sbces'];
    const COLUMN_SB_CESS     = 'sb_cess';
    const COLUMN_KK_CESS     = 'kk_cess';
    const COLUMN_FEE         = 'msf';
    const COLUMN_CARD_TRIVIA = 'card_type';
    const COLUMN_ISSUER      = 'arn_no';

    protected function getPaymentId($row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];
        $paymentId = trim(str_replace("'", '', $paymentId));
        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $columnServiceTax = null;

        foreach(self::COLUMN_SERVICE_TAX as $cst)
        {
            if (isset($row[$cst]) === true)
            {
                $columnServiceTax = $cst;
                break;
            }
        }

        if ($columnServiceTax === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_FAILURE,
                    'message'         => 'Unable to get the service tax!',
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            throw new ReconciliationException('Unable to get the service tax for HDFC from the recon file.');
        }

        // Convert service tax into paise
        $serviceTax = floatval($row[$columnServiceTax]) * 100;

        // Some hdfc reconciliation files have sb cess added to the service tax itself.
        // If sb cess is present separately, it means it's not added to the service tax.
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

    protected function getCardDetails($row)
    {
        // If the card type (debit/credit) is not present, we don't want
        // to store any of the other card details.
        if (isset($row[self::COLUMN_CARD_TYPE]) === false)
        {
            return null;
        }

        $columnCardType = strtolower($row[self::COLUMN_CARD_TYPE]);
        $columnCardTrivia = strtolower($row[self::COLUMN_CARD_TRIVIA]);

        $cardType = $this->getCardType($columnCardType, $row);
        $cardLocale = $this->getCardLocale($columnCardType, $row);
        $cardTrivia = $this->getCardTrivia($columnCardTrivia, $row);
        $issuer = $this->getIssuer($row);

        return [
            BaseReconciliate::CARD_TYPE   => $cardType,
            BaseReconciliate::CARD_LOCALE => $cardLocale,
            BaseReconciliate::CARD_TRIVIA => $cardTrivia,
            BaseReconciliate::ISSUER      => $issuer,
        ];
    }

    protected function getCardTrivia($cardTrivia, $row)
    {
        if (empty($cardTrivia) === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'recon_card_trivia' => $cardTrivia,
                    'row'               => $row,
                    'gateway'           => get_class()
                ]
            );

            $cardTrivia = null;
        }

        return $cardTrivia;
    }

    protected function getCardType($cardType, $row)
    {
        if ($cardType[1] === 'c')
        {
            $cardType = BaseReconciliate::CREDIT;
        }
        else if ($cardType[1] === 'd')
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
            $cardType = null;
        }

        return $cardType;
    }

    protected function getCardLocale($cardType, $row)
    {
        if ($cardType[0] === 'd')
        {
            $cardType = BaseReconciliate::DOMESTIC;
        }
        else if ($cardType[0] === 'f')
        {
            $cardType = BaseReconciliate::INTERNATIONAL;
        }
        else
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => 'Unable to figure out the card locale (domestic/international).',
                    'recon_card_type' => $cardType,
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            // It's as good as no card locale present in the row.
            return null;
        }

        return $cardType;
    }

    protected function getIssuer($row)
    {
        if (empty($row[self::COLUMN_ISSUER]) === true)
        {
            return null;
        }

        $columnIssuer = strtolower($row[self::COLUMN_PAYMENT_ID]);
        $columnIssuer = trim(str_replace("'", '', $columnIssuer));

        if (strpos($columnIssuer, 'onus') !== false)
        {
            return IFSC::HDFC;
        }

        return null;
    }
}
