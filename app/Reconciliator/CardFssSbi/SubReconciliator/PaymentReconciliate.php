<?php

namespace RZP\Reconciliator\CardFssSbi\SubReconciliator;

use RZP\Reconciliator\Base\SubReconciliator;

class PaymentReconciliate extends SubReconciliator\PaymentReconciliate
{
    const ONUS_INDICATOR = 'ONUS';

    const COLUMN_PAYMENT_AMOUNT = ReconciliationFields::TRANSACTION_AMOUNT;

    const BLACKLISTED_COLUMNS = [
        ReconciliationFields::CARD_NO,
    ];

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
        $rrn = $row[ReconciliationFields::PURCHASE_RRN] ?? null;

        return trim(str_replace("'", '', $rrn ?? null));
    }

    public function getArn($row)
    {
        $onusIndicator = $this->getOnusIndicator($row);

        $rrn = $this->getReferenceNumber($row);

        if (empty($rrn) === true)
        {
            $this->reportMissingColumn($row, ReconciliationFields::PURCHASE_RRN);
        }
        else if (strtolower($onusIndicator) === self::ONUS_INDICATOR)
        {
            // Only in case of ONUS transactions, we want to store RRN
            // In all the other cases, we want to store ARN only.
            // Currently, only ONUS transactions go through this gateways.
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
}
