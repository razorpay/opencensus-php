<?php

namespace RZP\Reconciliator\NetbankingJkb\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\NetbankingJkb\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::AMOUNT;

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getArn($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer'            =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
