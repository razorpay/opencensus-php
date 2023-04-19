<?php

namespace RZP\Reconciliator\PaylaterLazypay\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Reconciliator\PaylaterLazypay\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::TRANSACTION_AMOUNT;

    protected function getPaymentId(array $row)
    {
        if (empty($row[Reconciliate::MERCHANT_REFERENCE_NUMBER]) === false)
        {
            return trim($row[Reconciliate::MERCHANT_REFERENCE_NUMBER]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[Reconciliate::GATEWAY_PAYMENT_ID]) === true)
        {
            return $row[Reconciliate::GATEWAY_PAYMENT_ID];
        }

        return null;
    }

    protected function getArn($row)
    {
        $this->getReferenceNumber($row);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id' => $this->getReferenceNumber($row),
            'acquirer'           =>       [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }

    protected function getGatewayFee($row)
    {
        $gatewayFee = 0;

        if (isset($row[Reconciliate::MSF]) === false)
        {
            $this->reportMissingColumn($row, Reconciliate::MSF);

            return $gatewayFee;
        }

        $gatewayFee += Helper::getIntegerFormattedAmount($row[Reconciliate::MSF]);

        $serviceTax = $this->getGatewayServiceTax($row);

        $gatewayFee += $serviceTax;

        return $gatewayFee;
    }

    protected function getGatewayServiceTax($row)
    {
        $serviceTax = 0;

        if (isset($row[Reconciliate::IGST]) === false)
        {
            $this->reportMissingColumn($row, Reconciliate::IGST);

            return $serviceTax;
        }

        $gst = $row[Reconciliate::IGST];

        $serviceTax += Helper::getIntegerFormattedAmount($gst);

        return $serviceTax;
    }
}
