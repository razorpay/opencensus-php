<?php

namespace RZP\Reconciliator\NetbankingFsb\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingFsb\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::AMT;

    const PII_COLUMNS = [
        Reconciliate::ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::PRN] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $row[Reconciliate::ACCOUNT_NUMBER] ?? null
        ];
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::AMT]);
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

    protected function getGatewayPaymentDate($row)
    {
        return $row[Reconciliate::TXNDATE] ?? null;
    }
}
