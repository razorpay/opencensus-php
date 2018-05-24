<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Reconciliator\Base;
use RZP\Gateway\Cybersource;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\ReconciliationException;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID         = 'merchant_trackid';
    const COLUMN_CARD_TYPE          = 'debitcredit_type';
    const COLUMN_SERVICE_TAX        = ['serv_tax', 'service_tax', 'st_sbces'];
    const COLUMN_SB_CESS            = 'sb_cess';
    const COLUMN_KK_CESS            = 'kk_cess';
    const COLUMN_FEE                = 'msf';
    const COLUMN_CARD_TRIVIA        = 'card_type';
    const COLUMN_ISSUER             = 'arn_no';
    const COLUMN_CGST               = 'cgst_amt';
    const COLUMN_IGST               = 'igst_amt';
    const COLUMN_SGST               = 'sgst_amt';
    const COLUMN_UTGST              = 'utgst_amt';
    const COLUMN_ARN                = 'arn_no';
    const COLUMN_AUTH_CODE          = 'approv_code';

    const COLUMN_TERMINAL_NUMBER    = 'terminal_number';

    /**
     * If we are not able to find payment id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In HDFC MIS, many gst params and other params are always set to 0,
     * therefore if less than 10% of data is present, we don't mark row as failure.
     */
    const MIN_ROW_FILLED_DATA_RATIO = 0.10;

    /**
     * In case payment id is not found, function will return null,
     * row will be marked as failure in such case.
     *
     * @param array $row
     *
     * @return null|string
     */
    protected function getPaymentId(array $row)
    {
        if ($this->isCybersource($row) === true)
        {
            $paymentId = $this->getPaymentIdForCybersource($row);
        }
        else
        {
            $paymentId = $this->getPaymentIdForFss($row);
        }

        if (empty($paymentId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }

        return $paymentId;
    }

    protected function getPaymentIdForFss(array $row)
    {
        $paymentId = $this->getColumnPaymentId($row);

        //
        // For Cybersource payments via FSS, we get some ref number
        // instead of our payment ID.
        //
        if (UniqueIdEntity::verifyUniqueId($paymentId, false) === false)
        {
            return null;
        }

        return $paymentId;
    }

    protected function getPaymentIdForCybersource(array $row)
    {
        $paymentId = null;

        $ref = $this->getColumnPaymentId($row);

        //
        // The newer files have the actual
        // payment ID itself, like for FSS.
        //
        if (UniqueIdEntity::verifyUniqueId($ref, false) === true)
        {
            $paymentId = $ref;
        }
        else
        {
            //
            // The older files send some ref instead of our payment_id in
            // merchant_track_id column.
            //
            $gatewayPayment = $this->repo->cybersource->findSuccessfulTxnByActionAndRef(
                Cybersource\Action::AUTHORIZE, $ref);

            if ($gatewayPayment !== null)
            {
                $paymentId = $gatewayPayment->getPaymentId();
            }
            else
            {
                $this->trace->info(
                    TraceCode::RECON_MISMATCH,
                    [
                        'info_code' => 'PAYMENT_ABSENT',
                        'message'   => 'Payment not found. Skipping',
                        'row'       => $row,
                        'gateway'   => $this->gateway
                    ]);
            }
        }

        return $paymentId;
    }

    protected function getColumnPaymentId(array $row)
    {
        $paymentId = null;

        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            $paymentId = $row[self::COLUMN_PAYMENT_ID];

            $paymentId = trim(str_replace("'", '', $paymentId));
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
                    'gateway'         => $this->gateway
                ]);

            throw new ReconciliationException('Unable to get the service tax for HDFC from the recon file.');
        }

        // Convert service tax into paise
        $serviceTax = floatval($row[$columnServiceTax]) * 100;

        // Some hdfc reconciliation files have sb cess added to the service tax itself.
        // If sb cess is present separately, it means it's not added to the service tax.

        $sbCess = $this->getSbCess($row);
        $kkCess = $this->getKkCess($row);

        $serviceTax += $sbCess + $kkCess;

        $igst = $this->getIgst($row);
        $sgst = $this->getSgst($row);
        $cgst = $this->getCgst($row);
        $utgst = $this->getUtgst($row);

        $serviceTax += $igst + $sgst + $cgst + $utgst;

        return round($serviceTax);
    }

    protected function getIgst($row)
    {
        $columnIgst = null;

        //
        // This should be isset only and not empty
        // because igst can be 0 also.
        //
        if (isset($row[self::COLUMN_IGST]) === true)
        {
            $columnIgst = $row[self::COLUMN_IGST];
        }

        $igst = floatval($columnIgst) * 100;

        return $igst;
    }

    protected function getCgst($row)
    {
        $columnCgst = null;

        //
        // This should be isset only and not empty
        // because cgst can be 0 also.
        //
        if (isset($row[self::COLUMN_CGST]) === true)
        {
            $columnCgst = $row[self::COLUMN_CGST];
        }

        $cgst = floatval($columnCgst) * 100;

        return $cgst;
    }

    protected function getSgst($row)
    {
        $columnSgst = null;
        //
        // This should be isset only and not empty
        // because sgst can be 0 also.
        //
        if (isset($row[self::COLUMN_SGST]) === true)
        {
            $columnSgst = $row[self::COLUMN_SGST];
        }

        $sgst = floatval($columnSgst) * 100;

        return $sgst;
    }

    protected function getUtgst($row)
    {
        $columnUtgst = null;
        
        //
        // This should be isset only and not empty
        // because utgst can be 0 also.
        //
        if (isset($row[self::COLUMN_UTGST]) === true)
        {
            $columnUtgst = $row[self::COLUMN_UTGST];
        }

        $utgst = floatval($columnUtgst) * 100;

        return $utgst;
    }

    protected function getSbCess($row)
    {
        $columnSbCess = null;

        //
        // This should be isset only and not empty
        // because cess can be 0 also.
        //
        if (isset($row[self::COLUMN_SB_CESS]) === true)
        {
            $columnSbCess = $row[self::COLUMN_SB_CESS];
        }

        $sbCess = floatval($columnSbCess) * 100;

        return $sbCess;
    }

    protected function getKkCess($row)
    {
        $columnKkCess = null;

        //
        // This should be isset only and not empty
        // because cess can be 0 also.
        //
        if (isset($row[self::COLUMN_KK_CESS]) === true)
        {
            $columnKkCess = $row[self::COLUMN_KK_CESS];
        }

        $kkCess = floatval($columnKkCess) * 100;

        return $kkCess;
    }

    protected function getGatewayFee($row)
    {
        $columnFee = null;

        //
        // This should be isset only and not empty
        // because fee can be 0 also.
        //
        if (isset($row[self::COLUMN_FEE]) === true)
        {
            $columnFee = $row[self::COLUMN_FEE];
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

        if (empty($row[self::COLUMN_CARD_TYPE]) === false)
        {
            $cardType = $row[self::COLUMN_CARD_TYPE];
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

        if (empty($row[self::COLUMN_CARD_TRIVIA]) === false)
        {
            $cardTrivia = $row[self::COLUMN_CARD_TRIVIA];
        }

        if (empty($cardTrivia) === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'recon_card_trivia' => $cardTrivia,
                    'row'               => $row,
                    'gateway'           => $this->gateway
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
                    'gateway'         => $this->gateway
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
                    'gateway'         => $this->gateway
                ]);

            // It's as good as no card locale present in the row.
            return null;
        }

        return $cardType;
    }

    protected function getIssuer($row)
    {
        $columnIssuer = null;

        if (empty($row[self::COLUMN_ISSUER]) === false)
        {
            $columnIssuer = $row[self::COLUMN_ISSUER];
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

    protected function getArn($row)
    {
        $columnArn = null;

        if (empty($row[self::COLUMN_ARN]) === false)
        {
            $columnArn = $row[self::COLUMN_ARN];
        }

        if ((empty($columnArn) === true) or
            (stripos($columnArn, 'onus') !== false))
        {
            return null;
        }

        return trim(str_replace("'", '', $columnArn));
    }

    protected function getAuthCode($row)
    {
        $columnAuthCode = null;

        if (empty($row[self::COLUMN_AUTH_CODE]) === false)
        {
            $columnAuthCode = $row[self::COLUMN_AUTH_CODE];
        }

        if ((empty($columnAuthCode) === true))
        {
            return null;
        }

        return trim(str_replace("'", '', $columnAuthCode));
    }

    protected function isCybersource(array $row)
    {
        $terminalId = null;

        if (empty($row[self::COLUMN_TERMINAL_NUMBER]) === false)
        {
            $terminalId = $row[self::COLUMN_TERMINAL_NUMBER];

            $terminalId = trim(str_replace("'", '', $terminalId));
        }

        $isCybersource = (in_array($terminalId, Reconciliate::CYBERSOURCE_HDFC_TERMINAL_IDS, true) === true);

        return $isCybersource;
    }

    /**
     * This function evaluate and marks the row processing as success or failure based on
     * percentage of data available in a row.
     *
     * @param $row
     */
    protected function evaluateRowProcessedStatus(array $row)
    {
        $nonEmptyData = array_filter($row, function($value) {
            return ((filled($value)) and ($value !== "' "));
        });

        $rowFilledRatio = count($nonEmptyData) / count($row);

        if ($rowFilledRatio < self::MIN_ROW_FILLED_DATA_RATIO)
        {
            $this->setFailUnprocessedRow(false);
        }
    }
}