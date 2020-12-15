<?php

namespace RZP\Reconciliator\NetbankingIob\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingIob\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::AMOUNT;

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::REF_NUM] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REF_NO] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Reconciliate::STATUS];

        if ($status === Reconciliate::PAYMENT_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    protected function getArn($row)
    {
        return $row[Reconciliate::BANK_REF_NO] ?? null;
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
