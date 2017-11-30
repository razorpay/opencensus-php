<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     ******************/

    // session_id_aspd maps to caps_payment_id
    // comm_amount (commission amount) maps to gateway fee
    const COLUMN_CAPS_PAYMENT_ID = 'session_id_aspd';
    const COLUMN_GATEWAY_FEE     = 'comm_amount';
    const COLUMN_PAYMENT_AMOUNT  = 'transaction_amt';
    const COLUMN_CARD_CATEGORY   = 'card_category';
    const COLUMN_CARD_TRIVIA     = 'card_type';
    const COLUMN_AUTH_CODE       = 'auth_code';
    const COLUMN_ARN             = 'arn_no';

    const INTERNATIONAL          = 'international';
    const ONUS                   = 'onus';

    /**
     * The payment id in the file is under column 'SESSION ID ASPD'.
     * However, the id is sometimes capitalized, somethings not,
     * whereas the ids in our database are case sensitive.
     * So we compare ('SESSION ID ASPD') to 'caps_payment_id' of FirstData
     *
     * @param array   $row
     * @return string $paymentId
     */
    protected function getPaymentId($row)
    {
        $capsPaymentId = $row[self::COLUMN_CAPS_PAYMENT_ID];

        // doing this because sometimes the ids are all caps, sometimes not
        // the caps_payment_id in database is however all caps! :D
        // just to be on the safer side.
        $capsPaymentId = strtoupper($capsPaymentId);

        // The broad assumption here is that these ids will not collide
        // The mathematical probability is very low (not zero though)!
        $paymentId = $this->repo->first_data
                                ->findPaymentForGateway($capsPaymentId)
                                ->getPaymentId();

        return $paymentId;
    }

    /**
     * Gets amount captured.
     *
     * @param array $row
     * @return integer $paymentAmount
     */
    protected function getReconPaymentAmount($row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue
        return intval(number_format($paymentAmount, 2, '.', ''));
    }

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
     * Checks if payment amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
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
                    'gateway'           => get_class()
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
                    'gateway'           => get_class()
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
                    'gateway'           => get_class()
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
}
