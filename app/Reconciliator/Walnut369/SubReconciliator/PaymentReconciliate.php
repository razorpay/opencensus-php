<?php

namespace RZP\Reconciliator\Walnut369\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Walnut369\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::PURCHASE_OR_CANCELLED_AMOUNT;

    protected function getPaymentId(array $row)
    {
        if (empty($row[Reconciliate::RZP_TXN_ID]) === false)
        {
            return trim($row[Reconciliate::RZP_TXN_ID]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[Reconciliate::GATEWAY_PAYMENT_ID]) === true)
        {
            $referenceNumber = $row[Reconciliate::GATEWAY_PAYMENT_ID];

            return $referenceNumber;
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
        $mdr = 0;
        $partner_fee = 0;
        $net_transfer_amount = 0;

        if (isset($row[Reconciliate::MDR]) === true) {
            $mdr = floatval($row[Reconciliate::MDR]) * 100;
        }

        if (isset($row[Reconciliate::PARTNER_FEES]) === true) {
            $partner_fee = floatval($row[Reconciliate::PARTNER_FEES]) * 100;
        }

        if (isset($row[Reconciliate::NET_TRANSFER_AMOUNT]) === true) {
            $net_transfer_amount = floatval($row[Reconciliate::NET_TRANSFER_AMOUNT]) * 100;
        }

        $gateway_fee = $mdr + $partner_fee + $net_transfer_amount;

        return intval(number_format($gateway_fee, 2, '.', ''));

    }
}
