<?php

namespace RZP\Reconciliator\PaylaterLazypay\SubReconciliator;

use RZP\Reconciliator\Base;
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
}
