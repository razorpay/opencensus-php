<?php

namespace RZP\Reconciliator\NetbankingDcb\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingDcb\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::PAYMENT_AMOUNT;

    protected function getPaymentId(array $row)
    {
        if (isset($row[Reconciliate::PAYMENT_ID]) === true)
        {
            return trim($row[Reconciliate::PAYMENT_ID]);
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getArn($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::PAYMENT_AMOUNT]);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Reconciliate::PAYMENT_STATUS];

        if ($status === Reconciliate::PAYMENT_SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else
        {
            return Status::FAILED;
        }
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Reconciliate::PAYMENT_DATE] ?? null;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' => [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
