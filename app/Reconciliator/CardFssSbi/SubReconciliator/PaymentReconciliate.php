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
        $onusIndicator = $this->getOnusIndicator($row);

        $rrn = $this->getReferenceNumber($row);

        if ($onusIndicator === self::ONUS_INDICATOR)
        {
            // Only in case of ONUS transactions, we want to store RRN
            // In all the other cases, we want to store ARN only.
            return $rrn;
        }

        return null;
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

        $csfTax = (isset($row[ReconciliationFields::MTS_TOTL_CSF_AMT]) === true) ?
            (abs($row[ReconciliationFields::MTS_TOTL_CSF_AMT])) : 0;

        $gstTax = abs($row[ReconciliationFields::GST_AMT]);

        $tax = $gstTax + $csfTax;

        return SubReconciliator\Helper::getIntegerFormattedAmount($tax);
    }

    protected function getGatewayFee($row)
    {
        $msfAmount = SubReconciliator\Helper::getIntegerFormattedAmount($row[ReconciliationFields::MTS_MSF_FIXFEE]);

        $tax = $this->getGatewayServiceTax($row);

        return $msfAmount + $tax;
    }

    protected function getAuthCode($row)
    {
        $authCode = $row[ReconciliationFields::APPROVE_CODE] ?? null;

        if ($authCode === null)
        {
            $this->reportMissingColumn($row, implode(',', ReconciliationFields::APPROVE_CODE));

            return null;
        }

        // If the value is 088232 in sheet, the parsed value would be 88232. This prepends the required 0s
        $authCode = sprintf("%06d", $authCode);

        return $authCode;
    }
}
