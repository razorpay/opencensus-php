<?php

namespace RZP\Reconciliator\NetbankingNsdl\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingNsdl\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_PAYMENT_AMOUNT = Reconciliate::AMOUNT;

    const COLUMN_BANK_ACCOUNT_NUMBER = Reconciliate::ACCOUNTNO;

    const PII_COLUMNS = [
        self::COLUMN_BANK_ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::PGTXNID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANKREFNO] ?? null;
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::AMOUNT]);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Reconciliate::STATUS];

        if ($status === Reconciliate::SUCCESS_STATUS)
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

    protected function getArn($row)
    {
        return $row[Reconciliate::BANKREFNO] ?? null;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer'            =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => trim($row[self::COLUMN_BANK_ACCOUNT_NUMBER])
        ];
    }
}
