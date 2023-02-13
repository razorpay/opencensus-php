<?php

namespace RZP\Reconciliator\CardFssSbi\SubReconciliator;

use RZP\Reconciliator\Base\SubReconciliator;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    const ONUS_INDICATOR = 'onus';

    const COLUMN_PAYMENT_AMOUNT = ReconciliationFields::TRANSACTION_AMOUNT;

    public function getPaymentId(array $row)
    {
        $paymentId = $row[ReconciliationFields::MERCHANT_TXN_NO] ?? null;

        return trim(str_replace("'", '', $paymentId));
    }

    protected function getReconCurrency($row)
    {
        return trim($row[ReconciliationFields::TRANSACTION_CURRENCY] ?? null);
    }

    public function getReferenceNumber($row)
    {
        $rrn = $row[ReconciliationFields::TXN_REF] ?? null;

        return trim(str_replace("'", '', $rrn ?? null));
    }

    public function getArn($row)
    {
        // Removing dependency on onus indicator while saving arn.
        // Slack thread - https://razorpay.slack.com/archives/CGXKVCMAL/p1622102381070900

//        $onusIndicator = $this->getOnusIndicator($row);

        $arn = $row[ReconciliationFields::ARN] ?? null;

        return trim(str_replace("'", '', $arn ?? null));

//        if ($onusIndicator === self::ONUS_INDICATOR)
//        {
//            // Only in case of ONUS transactions, we want to store RRN
//            // In all the other cases, we want to store ARN only.
//            return $rrn;
//
    }


    protected function getOnusIndicator($row)
    {
        return strtolower($row[ReconciliationFields::ONUS_INDICATOR] ?? '');
    }

    protected function getGatewayServiceTax($row)
    {
        if (isset($row[ReconciliationFields::GST_AMT]) === false)
        {
            $this->reportMissingColumn($row, ReconciliationFields::GST_AMT);
        }

        return SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::GST_AMT]);
    }

    protected function getGatewayFee($row)
    {
        if (isset($row[ReconciliationFields::MDR]) === false)
        {
            $this->reportMissingColumn($row, ReconciliationFields::MDR);
        }

        $mdrAmount = SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::MDR]);

        return $mdrAmount;
    }

    protected function getAuthCode($row)
    {
        $authCode = $row[ReconciliationFields::APPROVE_CODE] ?? null;

        if ($authCode === null)
        {
            $this->reportMissingColumn($row, ReconciliationFields::APPROVE_CODE);

            return null;
        }

        // If the value is 088232 in sheet, the parsed value would be 88232. This prepends the required 0s
        $authCode = sprintf("%06s", $authCode);

        return $authCode;
    }
}
