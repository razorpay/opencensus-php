<?php

namespace RZP\Reconciliator\FirstData\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Reconciliator\Base;
use RZP\Models\Currency\Currency;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     ******************/

    // session_id_aspd maps to caps_payment_id
    // comm_amount (commission amount) maps to gateway fee
    const COLUMN_CAPS_PAYMENT_ID                = 'session_id_aspd';
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

    const SHOULD_ADD_ENTITY_ID_COLUMN = true;

    /**
     * The payment id in the file is under column 'SESSION ID ASPD'.
     * However, the id is sometimes capitalized, somethings not,
     * whereas the ids in our database are case sensitive.
     * So we compare ('SESSION ID ASPD') to 'caps_payment_id' of FirstData
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId(array $row)
    {
        $capsPaymentId = $row[self::COLUMN_CAPS_PAYMENT_ID];

        // doing this because sometimes the ids are all caps, sometimes not
        // the caps_payment_id in database is however all caps! :D
        // just to be on the safer side.
        $capsPaymentId = strtoupper($capsPaymentId);

        $paymentId = null;

        try
        {
            // The broad assumption here is that these ids will not collide
            // The mathematical probability is very low (not zero though)!
            $paymentId = $this->repo->first_data
                                    ->findPaymentForGateway($capsPaymentId)
                                    ->getPaymentId();
        }
        catch (DbQueryException $ex)
        {
            $this->trace->info(
                    TraceCode::RECON_MISMATCH,
                    [
                        'info_code'             => Base\InfoCode::PAYMENT_ABSENT,
                        'payment_reference_id'  => $capsPaymentId,
                        'gateway'               => $this->gateway
                    ]);

            $this->setFailUnprocessedRow(true);
        }

        return $paymentId;
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
        // Convert fee into basic unit of currency (ex: paise)
        $gatewayFee = floatval($row[self::COLUMN_GATEWAY_FEE]) * 100;

        return intval(number_format($gatewayFee, 2, '.', ''));
    }

    /**
     * We don't get service tax for First Data in the MIS files,
     * it is considered as zero
     *
     * @param  array $row
     * @return int 0
     */
    protected function getGatewayServiceTax($row)
    {
        return 0;
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

        $expectedCurrency = ($convertCurrency === true) ? Currency::INR : $this->payment->getCurrency();

        $reconCurrency = $row[self::COLUMN_CURRENCY] ?? null;

        if (strtoupper($expectedCurrency) !== strtoupper($reconCurrency))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::CURRENCY_MISMATCH,
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
