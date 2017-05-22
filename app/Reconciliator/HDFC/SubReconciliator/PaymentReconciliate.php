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
    const COLUMN_PAYMENT_ID  = ['merchant_trackid', 'MERCHANT_TRACKID'];
    const COLUMN_CARD_TYPE   = ['debitcredit_type', 'DEBITCREDIT_TYPE'];
    const COLUMN_SERVICE_TAX = ['serv_tax', 'service_tax', 'st_sbces', 'SERV TAX'];
    const COLUMN_SB_CESS     = ['sb_cess', 'SB Cess'];
    const COLUMN_KK_CESS     = ['kk_cess', 'KK Cess'];
    const COLUMN_FEE         = ['msf', 'MSF'];
    const COLUMN_CARD_TRIVIA = ['card_type', 'CARD TYPE'];
    const COLUMN_ISSUER      = ['arn_no', 'ARN NO'];

    protected function getPaymentId($row)
    {
        $paymentId = null;

        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (empty($row[$cpi]) === false)
            {
                $paymentId = $row[$cpi];

                $paymentId = trim(str_replace("'", '', $paymentId));

                break;
            }
        }

        return $paymentId;
    }

    protected function getGatewayServiceTax($row)
    {
        $columnServiceTax = null;

        foreach(self::COLUMN_SERVICE_TAX as $cst)
        {
            //
            // This should be isset only and not empty
            // because service tax can be 0 also.
            //
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

        $sbCess = $this->getSbCess();
        $kkCess = $this->getKkCess();

        $serviceTax += $sbCess + $kkCess;

        return round($serviceTax);
    }

    protected function getSbCess()
    {
        $columnSbCess = null;

        foreach(self::COLUMN_SB_CESS as $csc)
        {
            //
            // This should be isset only and not empty
            // because cess can be 0 also.
            //
            if (isset($row[$csc]) === true)
            {
                $columnSbCess = $row[$csc];
                break;
            }
        }

        $sbCess = floatval($columnSbCess) * 100;

        return $sbCess;
    }

    protected function getKkCess()
    {
        $columnKkCess = null;

        foreach(self::COLUMN_KK_CESS as $ckc)
        {
            //
            // This should be isset only and not empty
            // because cess can be 0 also.
            //
            if (isset($row[$ckc]) === true)
            {
                $columnKkCess = $row[$ckc];
                break;
            }
        }

        $kkCess = floatval($columnKkCess) * 100;

        return $kkCess;
    }

    protected function getGatewayFee($row)
    {
        $columnFee = null;

        foreach(self::COLUMN_FEE as $cf)
        {
            //
            // This should be isset only and not empty
            // because fee can be 0 also.
            //
            if (isset($row[$cf]) === true)
            {
                $columnFee = $row[$cf];
                break;
            }
        }

        // Convert fee into basic unit of currency (ex: paise)
        $fee = floatval($columnFee) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // HDFC reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
    }

    protected function getCardDetails($row)
    {
        $cardType = null;

        foreach (self::COLUMN_CARD_TYPE as $cct)
        {
            if (empty($row[$cct]) === false)
            {
                $cardType = $row[$cct];
            }
        }

        //
        // If the card type (debit/credit) is not present, we don't want
        // to store any of the other card details.
        //
        if (empty($cardType) === true)
        {
            return null;
        }

        $columnCardType = strtolower($cardType);

        $cardType = $this->getCardType($columnCardType, $row);
        $cardLocale = $this->getCardLocale($columnCardType, $row);
        $cardTrivia = $this->getCardTrivia($row);
        $issuer = $this->getIssuer($row);

        return [
            BaseReconciliate::CARD_TYPE   => $cardType,
            BaseReconciliate::CARD_LOCALE => $cardLocale,
            BaseReconciliate::CARD_TRIVIA => $cardTrivia,
            BaseReconciliate::ISSUER      => $issuer,
        ];
    }

    protected function getCardTrivia($row)
    {
        $cardTrivia = null;

        foreach (self::COLUMN_CARD_TRIVIA as $cct)
        {
            if (empty($row[$cct]) === false)
            {
                $cardTrivia = $row[$cct];

                break;
            }
        }

        if (empty($cardTrivia) === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'recon_card_trivia' => $cardTrivia,
                    'row'               => $row,
                    'gateway'           => get_class()
                ]);

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
        $columnIssuer = null;

        foreach (self::COLUMN_ISSUER as $ci)
        {
            if (empty($row[$ci]) === false)
            {
                $columnIssuer = $row[$ci];

                break;
            }
        }

        if (empty($columnIssuer) === true)
        {
            return null;
        }

        $columnIssuer = strtolower($columnIssuer);
        $columnIssuer = trim(str_replace("'", '', $columnIssuer));

        if (strpos($columnIssuer, 'onus') !== false)
        {
            return IFSC::HDFC;
        }

        return null;
    }
}
