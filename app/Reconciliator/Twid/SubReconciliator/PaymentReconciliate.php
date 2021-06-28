<?php

namespace RZP\Reconciliator\Twid\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Twid\Reconciliate;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::BILL_VALUE;

    protected function getPaymentId(array $row)
    {
        if (empty($row[Reconciliate::MERCHANT_TRANSACTION_ID]) === false)
        {
            return trim($row[Reconciliate::MERCHANT_TRANSACTION_ID]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (isset($row[Reconciliate::TRANSACTION_ID]) === true)
        {
            return $row[Reconciliate::TRANSACTION_ID];
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
