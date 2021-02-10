<?php

namespace RZP\Reconciliator\NetbankingUbi\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\NetbankingIdfc\Constants;
use RZP\Reconciliator\NetbankingUbi\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::AMOUNT;

    const PII_COLUMNS = [
        Reconciliate::ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::PAYMENT_ID] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER=> $this->getDebitAccountNumber($row)
        ];
    }

    protected function getDebitAccountNumber($row)
    {
        if (empty($row[Reconciliate::ACCOUNT_NUMBER]) === false)
        {
            return $row[Reconciliate::ACCOUNT_NUMBER];
        }

        return null;
    }

    protected function getArn($row)
    {
        return $row[Constants::BANK_REFERENCE_NO] ?? null;
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
