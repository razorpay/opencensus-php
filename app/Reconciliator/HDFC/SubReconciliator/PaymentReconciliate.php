<?php

namespace RZP\Reconciliator\HDFC\SubReconciliator;

use RZP\Gateway\Hdfc;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Reconciliator\Base;
use RZP\Gateway\Cybersource;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\HDFC\Reconciliate;
use RZP\Exception\ReconciliationException;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    use Base\BharatQrTrait;

    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID                     = 'merchant_trackid';
    const COLUMN_CARD_TYPE                      = 'debitcredit_type';
    const COLUMN_SERVICE_TAX                    = ['serv_tax', 'service_tax', 'servtax_usd', 'st_sbces'];
    const COLUMN_SB_CESS                        = ['sb_cess', 'sb_cess_usd'];
    const COLUMN_KK_CESS                        = ['kk_cess', 'kk_cess_usd'];
    const COLUMN_FEE                            = ['msf', 'discount_usd'];
    const COLUMN_CARD_TRIVIA                    = 'card_type';
    const COLUMN_ISSUER                         = 'arn_no';
    const COLUMN_CGST                           = ['cgst_amt', 'cgst_amt_usd'];
    const COLUMN_IGST                           = ['igst_amt', 'igst_amt_usd'];
    const COLUMN_SGST                           = ['sgst_amt', 'sgst_amt_usd'];
    const COLUMN_UTGST                          = ['utgst_amt','utgst_amt_usd'];
    const COLUMN_ARN                            = 'arn_no';

    const COLUMN_RRN                            = 'tran_id';
    const COLUMN_AUTH_CODE                      = 'approv_code';
    const COLUMN_SEQUENCE_NUMBER                = 'sequence_number';
    const COLUMN_MERCHANT_CODE                  = 'merchant_code';

    const COLUMN_TERMINAL_NUMBER                = 'terminal_number';
    const COLUMN_GATEWAY_TRANSACTION_ID         = 'tran_id';

    const COLUMN_PAYMENT_AMOUNT                 = ['domestic_amt', 'intnl_amt'];
    const COLUMN_INR_PAYMENT_AMOUNT             = 'inr';
    const COLUMN_INTERNATIONAL_PAYMENT_AMOUNT   = 'paycur_usd';

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
        else if ($this->isBharatQrIsg($row))
        {
            $transactionId = str_replace('\'', '', $row[self::COLUMN_GATEWAY_TRANSACTION_ID]);

            $amount = (int) ($row[self::COLUMN_PAYMENT_AMOUNT[0]] * 100);

            $paymentId = $this->getPaymentIdFromBharatQr($transactionId, $row, $amount);
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
                        'info_code'             => Base\InfoCode::PAYMENT_ABSENT,
                        'payment_reference_id'  => $ref,
                        'gateway'               => $this->gateway
                    ]);
            }
        }

        return $paymentId;
    }

    protected function isBharatQrIsg(array $row)
    {
        if ((isset($row[self::COLUMN_CARD_TRIVIA]) === true) and
            ($row[self::COLUMN_CARD_TRIVIA] === Reconciliate::BHARAT_QR_TYPE))
        {
            return true;
        }

        return false;
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

    protected function getReconPaymentAmount(array $row)
    {
        $amountColumn = ($this->isInternationalPayment($row) === true) ?
                        self::COLUMN_INTERNATIONAL_PAYMENT_AMOUNT :
                        self::COLUMN_PAYMENT_AMOUNT;

        $paymentAmountColumns = (is_array($amountColumn) === false) ?
                                [$amountColumn] :
                                $amountColumn;

        $paymentAmountColumn = array_first(
            $paymentAmountColumns,
            function ($amount) use ($row)
            {
                return (array_key_exists($amount, $row) === true);
            });

        if ($paymentAmountColumn === null)
        {
            // None of the expected payment columns set
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_ABSENT,
                    'payment_id'        => $this->payment->getId(),
                    'expected_column'   => $amountColumn,
                    'amount'            => $this->payment->getBaseAmount(),
                    'currency'          => $this->payment->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        //
        // for HDFC, sometimes we are getting rows where 'domestic_amt' column is 0
        // and amount is given in 'intnl_amt' column. In this case, we can't just calculate
        // the recon amount on first key set. Instead we should calculate on the non zero
        // amount column if any.
        //
        $paymentAmountNonZeroColumn = array_first(
            $paymentAmountColumns,
            function ($amount) use ($row)
            {
                return (empty($row[$amount]) === false);
            });

        if ($paymentAmountNonZeroColumn === null)
        {
            // There was no non zero amount column, so returning 0
            return 0;
        }

        return Helper::getIntegerFormattedAmount($row[$paymentAmountNonZeroColumn]);
    }

    /**
     * @param $row
     * @return float|null
     * @throws ReconciliationException
     */
    protected function getGatewayServiceTax($row)
    {
        $columnServiceTax = null;

        $columnServiceTax = array_first(self::COLUMN_SERVICE_TAX, function ($cst) use ($row)
        {
            //
            // This should be isset only and not empty
            // because service tax can be 0 also.
            //
            return (isset($row[$cst]) === true);
        });

        if ($columnServiceTax === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_FAILURE,
                    'message'         => 'Unable to get the service tax!',
                    'payment_id'      => $this->payment->getId(),
                    'gateway'         => $this->gateway
                ]);

            throw new ReconciliationException('Unable to get the service tax for HDFC from the recon file.');
        }

        // Convert service tax into paise
        $serviceTax = Helper::getIntegerFormattedAmount($row[$columnServiceTax]);

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

        $serviceTax = $serviceTax * $this->getCurrencyConversionRate($row);

        return round($serviceTax);
    }

    protected function getIgst($row)
    {
        $columnIgst = null;

        $columnIgst = array_first(self::COLUMN_IGST, function ($cIgst) use ($row)
        {
            //
            // This should be isset only and not empty
            // because igst can be 0 also.
            //
            return (isset($row[$cIgst]) === true);
        });

        $igst = ($columnIgst !== null) ? $row[$columnIgst] : null;

        return Helper::getIntegerFormattedAmount($igst);
    }

    protected function getCgst($row)
    {
        $columnCgst = null;

        $columnCgst = array_first(self::COLUMN_CGST, function ($cGst) use ($row)
        {
            //
            // This should be isset only and not empty
            // because cgst can be 0 also.
            //
            return (isset($row[$cGst]) === true);
        });

        $cgst = ($columnCgst !== null) ? $row[$columnCgst] : null;

        return Helper::getIntegerFormattedAmount($cgst);
    }

    protected function getSgst($row)
    {
        $columnSgst = null;

        $columnSgst = array_first(self::COLUMN_SGST, function ($sgst) use ($row)
        {
            //
            // This should be isset only and not empty
            // because sgst can be 0 also.
            //
            return (isset($row[$sgst]) === true);
        });

        $sgst = ($columnSgst !== null) ? $row[$columnSgst] : null;

        return Helper::getIntegerFormattedAmount($sgst);
    }

    protected function getUtgst($row)
    {
        $columnUtGst = null;

        $columnUtGst = array_first(self::COLUMN_UTGST, function ($utGst) use ($row)
        {
            //
            // This should be isset only and not empty
            // because UtGst can be 0 also.
            //
            return (isset($row[$utGst]) === true);
        });

        $utGst = ($columnUtGst !== null) ? $row[$columnUtGst] : null;

        return Helper::getIntegerFormattedAmount($utGst);
    }

    protected function getSbCess($row)
    {
        $columnSbCess = null;

        $columnSbCess = array_first(self::COLUMN_SB_CESS, function ($sbCess) use ($row)
        {
            //
            // This should be isset only and not empty
            // because sbCess can be 0 also.
            //
            return (isset($row[$sbCess]) === true);
        });

        $sbCess = ($columnSbCess !== null) ? $row[$columnSbCess] : null;

        return Helper::getIntegerFormattedAmount($sbCess);
    }

    protected function getKkCess($row)
    {
        $columnKkCess = null;

        $columnKkCess = array_first(self::COLUMN_KK_CESS, function ($kkCess) use ($row)
        {
            //
            // This should be isset only and not empty
            // because kkCess can be 0 also.
            //
            return (isset($row[$kkCess]) === true);
        });

        $kkCess = ($columnKkCess !== null) ? $row[$columnKkCess] : null;

        return Helper::getIntegerFormattedAmount($kkCess);
    }

    /**
     * @param $row
     * @return float|null
     * @throws ReconciliationException
     */
    protected function getGatewayFee($row)
    {
        $columnFee = null;

        $columnFee = array_first(self::COLUMN_FEE, function ($fee) use ($row)
        {
            //
            // This should be isset only and not empty
            // because fee can be 0 also.
            //
            return (isset($row[$fee]) === true);
        });

        $fee = ($columnFee !== null) ? $row[$columnFee] : null;

        // Convert fee into basic unit of currency (ex: paise)
        $fee =  Helper::getIntegerFormattedAmount($fee);

        //
        // If payment is international, we may want to get currency conversion rate to
        // get fee in INR.
        //
        $fee = $fee * $this->getCurrencyConversionRate($row);

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
                    'payment_id'        => $this->payment->getId(),
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
                    'payment_id'      => $this->payment->getId(),
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

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
    }

    protected function getAuthCode($row)
    {
        $columnAuthCode = null;

        if (empty($row[self::COLUMN_AUTH_CODE]) === false)
        {
            $columnAuthCode = $row[self::COLUMN_AUTH_CODE];
        }

        if (empty($columnAuthCode) === true)
        {
            return null;
        }

        return trim(str_replace("'", '', $columnAuthCode));
    }

    protected function getGatewayTransactionId(array $row)
    {
        $gatewayPaymentId = null;

        if (empty($row[self::COLUMN_GATEWAY_TRANSACTION_ID]) === false)
        {
            $gatewayPaymentId = $row[self::COLUMN_GATEWAY_TRANSACTION_ID];
        }

        if (empty($gatewayPaymentId) === true)
        {
            return null;
        }

        return trim(str_replace("'", '', $gatewayPaymentId));
    }

    protected function getSequenceNumber(array $row)
    {
        $sequenceNumber = null;

        if (empty($row[self::COLUMN_SEQUENCE_NUMBER]) === false)
        {
            $sequenceNumber = $row[self::COLUMN_SEQUENCE_NUMBER];
        }

        if (empty($sequenceNumber) === true)
        {
            return null;
        }

        return trim(str_replace("'", '', $sequenceNumber));
    }

    protected function isCybersource(array $row)
    {
        $terminalId = null;

        if (empty($row[self::COLUMN_TERMINAL_NUMBER]) === false)
        {
            $terminalId = $row[self::COLUMN_TERMINAL_NUMBER];

            $terminalId = trim(str_replace("'", '', $terminalId));
        }

        $isCybersource = Reconciliate::isCybersourceTerminalId($terminalId);

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

    /**
     * In case of Non INR payments, we want to find conversion rate
     * to convert non INR service tax and fee into INR
     * @param array $row
     * @return float|int|null
     */
    protected function getCurrencyConversionRate(array $row)
    {
        $conversionRate = 1;

        if ($this->isInternationalPayment($row) === true)
        {
            $inrAmount = $row[self::COLUMN_INR_PAYMENT_AMOUNT];

            $inrAmount = Base\SubReconciliator\Helper::getIntegerFormattedAmount($inrAmount);

            $internationalAmount = $this->getReconPaymentAmount($row);

            $conversionRate = (empty($internationalAmount) === false) ? ($inrAmount / $internationalAmount) : 1;
        }

        return $conversionRate;
    }

    /**
     * In case on Non INR payments, a non empty field of 'inr' is set in transaction row and
     * convert_currency will be false for such payment.
     * @param array $row
     * @return bool
     */
    protected function isInternationalPayment(array $row)
    {
        $inrAmountColumnSet =  (empty($row[self::COLUMN_INR_PAYMENT_AMOUNT]) === false) ? true : false;

        $convertCurrencyFlag = $this->payment->getConvertCurrency();

        if (($inrAmountColumnSet === true) and ($convertCurrencyFlag === false))
        {
            return true;
        }

        return false;
    }

    /**
     * For HDFC, sometimes the capture request getting timed out, so
     * we need to create a capture entry if it is not present.
     *
     * @param array $row
     */
    protected function createGatewayCapturedEntityIfApplicable(array $row)
    {
        if ($this->shouldCreateGatewayEntity($row) === false)
        {
            return;
        }

        $attributes = [
            Hdfc\Entity::PAYMENT_ID             => $this->payment->getId(),
            Hdfc\Entity::ACTION                 => Hdfc\Payment\Action::CAPTURE,
            Hdfc\Fields::GATEWAY_TRANSACTION_ID => $this->getGatewayTransactionId($row),
            Hdfc\Fields::AMOUNT_FULL            => $this->getReconPaymentAmount($row) / 100,
            Hdfc\Fields::STATUS                 => Hdfc\Payment\Status::CAPTURED,
            Hdfc\Fields::RESULT                 => strtoupper(Hdfc\Payment\Status::CAPTURED),
            Hdfc\Fields::AUTH                   => $this->getAuthCode($row),
        ];

        if ($this->isDataAvailableForCaptureEntity($attributes) === false)
        {
            return;
        }

        // 'ref' is optional, that is why added after the previous check
        $attributes[Hdfc\Fields::REF] = $this->getSequenceNumber($row);

        try
        {
            $gatewayPayment = (new Hdfc\Gateway)->createGatewayEntity($attributes);

            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'             => Base\InfoCode::RECON_GATEWAY_ENTITY_CREATED,
                    'payment_id'            => $this->payment->getId(),
                    'gateway_payment_id'    => $gatewayPayment->getId(),
                    'gateway'               => $this->gateway,
                ]);
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => Base\InfoCode::RECON_GATEWAY_ENTITY_CREATION_FAILED,
                    'payment_id'    => $this->payment->getId(),
                    'attributes'    => $attributes,
                    'error'         => $ex->getMessage(),
                    'gateway'       => $this->gateway,
                ]);
        }
    }

    protected function shouldCreateGatewayEntity($row)
    {
        if (($this->isCybersource($row) === true) or
            ($this->isBharatQrIsg($row) === true))
        {
            return false;
        }

        try
        {
            // Check if gateway entity already exists
            $entity = $this->repo->hdfc->retrieveCapturedOrAcceptedCaptureFailures($this->payment->getId());

            if (empty($entity) === false)
            {
                // Entity exists
                return false;
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'   => 'Exception encountered while trying to fetch HDFC captured gateway entity.',
                    'exception' => $ex->getMessage(),
                ]);

            //
            // We do not know if the entity exists or not, so
            // we should not create new entity in such case.
            //
           return false;
        }

        return true;
    }

    protected function isDataAvailableForCaptureEntity($attributes)
    {
        //
        // If any attribute value is null/empty, return false
        // bcoz we need these attributes to create the entity.
        //
        foreach ($attributes as $attribute => $value)
        {
            if (empty($value) === true)
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code'  => Base\InfoCode::RECON_INSUFFICIENT_DATA_FOR_ENTITY_CREATION,
                        'message'    => 'Required attribute is null. Can not create gateway entity.',
                        'attribute'  => $attribute,
                        'payment_id' => $this->payment->getId(),
                        'gateway'    => $this->gateway,
                    ]);

                return false;
            }
        }

        return true;
    }
}
