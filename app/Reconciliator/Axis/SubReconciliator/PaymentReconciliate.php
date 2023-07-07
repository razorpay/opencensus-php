<?php

namespace RZP\Reconciliator\Axis\SubReconciliator;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Reconciliator\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Cybersource;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID     = ['merchant_trans_ref', 'merchant_tran_ref'];
    const COLUMN_CARD_TYPE      = 'card_type';
    const COLUMN_SERVICE_TAX    = ['service_tax', 'service_taxat145', 'service_taxat1450',
                                  'service_taxat135', 'service_taxat1350', 'service_taxat1500'];
    const COLUMN_GST            = ['gst'];
    const COLUMN_FEE            = 'commission';
    const COLUMN_CARD_TRIVIA    = ['card', 'network', 'card_category'];
    const COLUMN_ORDER_ID       = 'order_id';
    const COLUMN_CARD_LOCALE    = 'lofo';
    const COLUMN_ISSUER         = 'transaction_category';
    const COLUMN_SETTLED_AT     = 'settlement_date';
    const COLUMN_MSG_TYPE       = 'msg_type';
    const COLUMN_MID            = 'mid';
    const COLUMN_ARN            = 'arn';
    const COLUMN_RRN            = 'rrn no';
    const COLUMN_AUTH_CODE      = 'appr_code';

    const PREAUTH               = 'PREAUTH';
    const CYBS                  = 'CYBS';

    const COLUMN_PAYMENT_AMOUNT = 'txn_amount';

    const POSSIBLE_DATE_FORMATS = [
        'd-M-y',
        'Y-m-d h:i:s'
    ];

    /**
     * If we are not able to find payment id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In old Axis MIS format, last row has around 9 out of 34 columns as stats data and
     * rest empty. (9/34)*100 = 26.47 %
     *
     * In new Axis MIS format, last row has around 10 out of 35 columns as stats data and
     * rest empty. (10/36)*100 = 27.77 %
     *
     * Therefore, if less than 28% of data is present, we don't mark row as failure
     */
    const MIN_ROW_FILLED_DATA_RATIO = 0.28;

    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        if ($this->isCybersource($row) === true)
        {
            $paymentId = $this->getPaymentIdForCybersource($row);
        }
        else
        {
            $paymentId = $this->getPaymentIdForMigs($row);
        }

        if (empty($paymentId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }

        return $paymentId;
    }

    protected function getPaymentIdForMigs(array $row)
    {
        $paymentId = null;

        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (empty($row[$cpi]) === false)
            {
                $paymentId = trim($row[$cpi]);

                break;
            }
        }

        //
        // For Cybersource payments via Axis, we don't get a payment ID
        // in the self::COLUMN_PAYMENT_ID. We get some reference number.
        // This usually means that it's a Cybersource payment and we have
        // to get payment_id in a different way.
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

        //
        // First we check if we have received payment id in the row,
        // If not, then only we check gateway payment using the order id.
        //
        foreach (self::COLUMN_PAYMENT_ID as $cpi)
        {
            if (empty($row[$cpi]) === false)
            {
                $pid = trim($row[$cpi]);

                if (UniqueIdEntity::verifyUniqueId($pid, false) === true)
                {
                    // Valid payment id
                    $paymentId = $pid;

                    break;
                }
            }
        }

        if (empty($paymentId) === false)
        {
            return $paymentId;
        }

        $orderId = trim($row[self::COLUMN_ORDER_ID]);

        $gatewayPayment = $this->repo->cybersource->findSuccessfulTxnByActionAndRef(
                                                        Cybersource\Action::CAPTURE, $orderId);

        if ($gatewayPayment !== null)
        {
            $paymentId = $gatewayPayment->getPaymentId();
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'             => Base\InfoCode::PAYMENT_ABSENT,
                    'payment_reference_id'  => $orderId,
                    'gateway'               => $this->gateway
                ]);
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
                $columnServiceTax = $row[$cst];
                break;
            }
        }

        // Convert service tax into basic unit of currency. (ex: paise)
        $serviceTax = floatval($columnServiceTax) * 100;

        $gst = $this->getGst($row);

        $serviceTax += $gst;

        return round($serviceTax);
    }

    protected function getGst(array $row)
    {
        $columnGst = null;

        foreach(self::COLUMN_GST as $cgst)
        {
            //
            // This should be isset only and not empty
            // because gst can be 0 also.
            //
            if (isset($row[$cgst]) === true)
            {
                $columnGst = $row[$cgst];
                break;
            }
        }

        $gst = floatval($columnGst) * 100;

        return $gst;
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency. (ex: paise)
        $fee = floatval($row[self::COLUMN_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        // Axis reconciliation files have fee and service tax separately
        $fee += $serviceTax;

        return round($fee);
    }

    protected function getCardDetails($row)
    {
        // If the card type (debit/credit) is not present, we don't want
        // to store any of the other card details.
        if (empty($row[self::COLUMN_CARD_TYPE]) === true)
        {
            return null;
        }

        $columnCardType = strtolower($row[self::COLUMN_CARD_TYPE]);
        $columnCardTrivia = $this->getColumnCardTrivia($row);
        $columnCardLocale = $this->getColumnCardLocale($row);

        $cardType = $this->getCardType($columnCardType, $row);
        $cardLocale = $this->getCardLocale($columnCardLocale, $row);
        $cardTrivia = $this->getCardTrivia($columnCardTrivia, $row);
        $issuer = $this->getIssuer($row);

        return [
            BaseReconciliate::CARD_TYPE   => $cardType,
            BaseReconciliate::CARD_TRIVIA => $cardTrivia,
            BaseReconciliate::CARD_LOCALE => $cardLocale,
            BaseReconciliate::ISSUER      => $issuer,
        ];
    }

    protected function getIssuer($row)
    {
        if (empty($row[self::COLUMN_ISSUER]) === true)
        {
            return null;
        }

        $columnIssuer = strtolower($row[self::COLUMN_ISSUER]);

        if ($columnIssuer === 'onus')
        {
            return IFSC::UTIB;
        }

        return null;
    }

    protected function getColumnCardLocale($row)
    {
        $columnCardLocale = null;

        if (empty($row[self::COLUMN_CARD_LOCALE]) === false)
        {
            $columnCardLocale = strtolower($row[self::COLUMN_CARD_LOCALE]);
        }

        return $columnCardLocale;
    }

    protected function getColumnCardTrivia($row)
    {
        $columnCardTrivia = null;

        foreach (self::COLUMN_CARD_TRIVIA as $cct)
        {
            if (empty($row[$cct]) === false)
            {
                $columnCardTrivia = $cct;
                break;
            }
        }

        if ($columnCardTrivia === null)
        {
            return null;
        }

        return $row[$columnCardTrivia];
    }

    protected function getCardTrivia($cardTrivia, $row)
    {
        if (empty($cardTrivia) === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'info_code'         => 'CARD_TRIVIA_ABSENT',
                    'payment_id'        => $this->payment->getId(),
                    'recon_card_trivia' => $cardTrivia,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]
            );

            $cardTrivia = null;
        }

        return $cardTrivia;
    }

    protected function getCardType($cardType, $row)
    {
        if (($cardType === 'c') or ($cardType === 'credit'))
        {
            $cardType = BaseReconciliate::CREDIT;
        }
        else if (($cardType === 'd') or ($cardType === 'debit'))
        {
            $cardType = BaseReconciliate::DEBIT;
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => 'Unable to figure out the card type.',
                    'recon_card_type' => $cardType,
                    'payment_id'      => $this->payment->getId(),
                    'gateway'         => $this->gateway
                ]);

            // It's as good as no card type present in the row.
            $cardType = null;
        }

        return $cardType;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'vpc_TransactionNo' => $row[self::COLUMN_ORDER_ID],
            'authRespCode' => $row[self::COLUMN_AUTH_CODE]
        ];
    }

    protected function getCardLocale($cardLocale, $row)
    {
        if (empty($cardLocale) === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card locale. This is unexpected.',
                    'info_code'         => 'CARD_LOCALE_ABSENT',
                    'payment_id'        => $this->payment->getId(),
                    'recon_card_trivia' => $cardLocale,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return null;
        }

        if (($cardLocale === 'l') or ($cardLocale === 'local'))
        {
            $cardType = BaseReconciliate::DOMESTIC;
        }
        else if (($cardLocale === 'f') or ($cardLocale === 'foreign'))
        {
            $cardType = BaseReconciliate::INTERNATIONAL;
        }
        else
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card locale. This is unexpected.',
                    'info_code'         => 'CARD_LOCALE_ABSENT',
                    'recon_card_trivia' => $cardLocale,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            // It's as good as no card locale present in the row.
            return null;
        }

        return $cardType;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

        $gatewaySettledAt = null;

        foreach (self::POSSIBLE_DATE_FORMATS as $possibleDateFormat)
        {
            try
            {
                $gatewaySettledAt = Carbon::createFromFormat($possibleDateFormat, $columnSettledAt, Timezone::IST);
                $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
            }
            catch (\Exception $ex)
            {
                continue;
            }
        }

        return $gatewaySettledAt;
    }

      /**
     * This is a uniqueId generated by gateway. It is similar to paymentId. This is generated in response of capture call
     *
     * @param $row
     * @return null
     */
    protected function getGatewayReferenceId2(array $row)
    {
          $orderId = trim($row[self::COLUMN_ORDER_ID]);

          return $orderId ;
    }

    protected function getArn($row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_ARN);

            return null;
        }

        return $row[self::COLUMN_ARN];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getAuthCode($row)
    {
        if (empty($row[self::COLUMN_AUTH_CODE]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_AUTH_CODE);

            return null;
        }

        return $row[self::COLUMN_AUTH_CODE];
    }

    protected function isCybersource(array $row)
    {
        $msgType = $mid = null;

        if (isset($row[self::COLUMN_MSG_TYPE]) === true)
        {
            $msgType = $row[self::COLUMN_MSG_TYPE];
        }

        if (isset($row[self::COLUMN_MID]) === true)
        {
            $mid = $row[self::COLUMN_MID];
        }

        if ((stripos($msgType, self::PREAUTH) !== false) or
            (ends_with($mid, self::CYBS) === true))
        {
            return true;
        }

        return false;
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = true;
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
            return filled($value);
        });

        $rowFilledRatio = count($nonEmptyData) / count($row);

        if ($rowFilledRatio < self::MIN_ROW_FILLED_DATA_RATIO)
        {
            $this->setFailUnprocessedRow(false);
        }
    }
}
