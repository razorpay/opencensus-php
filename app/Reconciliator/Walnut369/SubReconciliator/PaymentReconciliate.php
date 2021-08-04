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
}
