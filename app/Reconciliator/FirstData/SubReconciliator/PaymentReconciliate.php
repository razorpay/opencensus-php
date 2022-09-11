<?php

namespace RZP\Reconciliator\FirstData\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Reconciliator\Base;
use RZP\Models\Currency\Currency;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     ******************/

    // comm_amount (commission amount) maps to gateway fee
    const COLUMN_RZP_ENTITY_ID                  = 'session_id_aspd';
    const COLUMN_GATEWAY_FEE                    = 'comm_amount';
    const COLUMN_CARD_CATEGORY                  = 'card_category';
    const COLUMN_CARD_TRIVIA                    = 'card_type';
    const COLUMN_AUTH_CODE                      = 'auth_code';
    const COLUMN_ARN                            = 'arn_no';
    const INTERNATIONAL                         = 'international';
    const ONUS                                  = 'onus';
    const COLUMN_CURRENCY                       = 'transaction_currency';
    const COLUMN_PAYMENT_AMOUNT                 = 'transaction_amt';
    const COLUMN_INTERNATIONAL_PAYMENT_AMOUNT   = 'transaction_amt';
    const COLUMN_RRN                            = 'ret_ref_num';

    /**
     * In actual MIS file we still get Caps PID,
     * but we have pre processed the file to
     * replace the caps PID with actual PIDs..
     * so simply returning the value here.
     * @param array $row
     * @return mixed|null
     */
    protected function getPaymentId(array $row)
    {
        return $row[self::COLUMN_RZP_ENTITY_ID] ?? null;
    }

    /**
     * Gets amount captured.
     *
     * @param array $row
     * @return integer $paymentAmount
     */

    /**
     * Gateway Fee is given as commission amount.
     * Seems like a consolidated amount, can be returned as is.
     * We get a value like 11.35 for this column.
     *
     * Usually we add service tax to this value,
     * but ST for first data is 0, hence, no addition.
     *
     * @param array    $row
     * @return integer $gatewayFee
     */
    protected function getGatewayFee($row)
    {
        // Removing comma from fee, as floatval takes value before last dot or comma
        $formattedAmount = str_replace(',', '', $row[self::COLUMN_GATEWAY_FEE]);

        // Convert fee into basic unit of currency (ex: paise)
        $gatewayFee = floatval($formattedAmount) * 100;

        $serviceTax = $this->getGatewayServiceTax($row);

        $gatewayFee += $serviceTax;

        return intval(number_format($gatewayFee, 2, '.', ''));
    }

    /**
     * We don't get service tax for First Data in the MIS files,
     * it is considered as zero
     *
     * @param array $row
     * @return int
     */
    protected function getGatewayServiceTax($row)
    {
        $serviceTax = 0;

        // If total transaction amount is greater than 2000 then
        // gst is 18% of commission

        $formattedAmount = str_replace(',', '', $row[self::COLUMN_PAYMENT_AMOUNT]);

        $transactionAmt = floatval($formattedAmount) * 100;

        $transactionAmt = intval(number_format($transactionAmt, 2, '.', ''));

        if ($transactionAmt > 200000)
        {
            $formattedAmount = str_replace(',', '', $row[self::COLUMN_GATEWAY_FEE]);

            // Convert fee into basic unit of currency (ex: paise)
            $gatewayFee = floatval($formattedAmount) * 100;

            $gatewayFee = intval(number_format($gatewayFee, 2, '.', ''));

            $serviceTax = (18 / 100) * $gatewayFee;
        }

       return $serviceTax;
    }

    /**
     * @param array $row
     * @return bool
     */
    protected function isInternationalPayment(array $row)
    {
        $convertCurrencyFlag = $this->payment->getConvertCurrency();

        $isNonInrCurrency = (strtoupper($row[self::COLUMN_CURRENCY] ?? null) !== Currency::INR);

        if (($isNonInrCurrency === true) and ($convertCurrencyFlag === false))
        {
            return true;
        }

        return false;
    }

    /**
     * Sets card details like locale, trivia & issuer.
     * Card type (debit/credit) in unavailable from given data.
     *
     * @param  array $row
     * @return array
     */
    protected function getCardDetails($row)
    {
        return [
            BaseReconciliate::CARD_LOCALE => $this->getCardLocale($row),
            BaseReconciliate::CARD_TRIVIA => $this->getCardTrivia($row),
            BaseReconciliate::ISSUER      => $this->getIssuer($row),
        ];
    }

    /**
     * Determines whether the card is international or domestic
     *
     * @param  array $row
     * @return string
     */
    protected function getCardLocale($row)
    {
        if (empty($row[self::COLUMN_CARD_CATEGORY]) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card locale. This is unexpected.',
                    'info_code'         => 'CARD_LOCALE_ABSENT',
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]
            );

            // there is an anomaly if no card category is present in row
            return null;
        }

        $categoryString = $row[self::COLUMN_CARD_CATEGORY];

        // using stripos 'cuz unsure of case in value we get from row
        if (stripos($categoryString, self::INTERNATIONAL))
        {
            return BaseReconciliate::INTERNATIONAL;
        }

        //
        // In case some card is actually international,
        // but they haven't mentioned in the card_category column,
        // but it's already marked as international in our DB,
        // we don't want to override it with domestic
        //
        return null;
    }

    /**
     * Sets value of column 'card_type' as card trivia
     *
     * @param  array  $row
     * @return string $cardType
     */
    protected function getCardTrivia($row)
    {
        if (empty($row[self::COLUMN_CARD_TRIVIA]) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card trivia. This is unexpected.',
                    'info_code'         => 'CARD_TRIVIA_ABSENT',
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            // there is an anomaly if no card type is present in row
            return null;
        }

        $cardType = $row[self::COLUMN_CARD_TRIVIA];

        return $cardType;
    }

    /**
     * We basically check the value of column card_category
     * If the string has 'onus', then the issuer is ICIC
     * In other cases, it's indeterminate
     *
     * @param  array  $row
     * @return string $issuer
     */
    protected function getIssuer($row)
    {
        if (empty($row[self::COLUMN_CARD_CATEGORY]) === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'           => 'Unable to get the card issuer.',
                    'info_code'         => 'CARD_ISSUER_ABSENT',
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            // there is an anomaly if no card category is present in row
            return null;
        }

        $issuerString = $row[self::COLUMN_CARD_CATEGORY];

        // using stripos 'cuz unsure of case in value we get from row
        if (stripos($issuerString, self::ONUS))
        {
            return IFSC::ICIC;
        }

        return null;
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

    protected function getReferenceNumber($row)
    {
        return $row[self::COLUMN_RRN] ?? null;
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

    /**
     * This returns the array of attributes to be saved while force authorizing the payment.
     *
     * @param $row
     * @return array
     */
    protected function getInputForForceAuthorize($row)
    {
        return [
            BaseReconciliate::AUTH_CODE => $this->getAuthCode($row)
        ];
    }

    /**
     * @param array $row
     * @return bool
     */
    protected function validatePaymentCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? Currency::INR : $this->payment->getGatewayCurrency();

        $reconCurrency = $row[self::COLUMN_CURRENCY] ?? null;

        if ((strtoupper($expectedCurrency) !== strtoupper($reconCurrency)) and (empty($reconCurrency) !== true))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::CURRENCY_MISMATCH,
                    'payment_id'        => $this->payment->getId(),
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
