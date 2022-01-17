<?php

namespace RZP\Reconciliator\NetbankingUco\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\NetbankingUco\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::AMT;

    const PII_COLUMNS = [
        Reconciliate::ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::MERCHANT_REFERENCE_NO] ?? null;
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

    protected function getDebitAccountNumber($row)
    {
        if (empty($row[Reconciliate::ACCOUNT_NUMBER]) === false) {
            return $row[Reconciliate::ACCOUNT_NUMBER];
        }

        return null;
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::AMT]);
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[Reconciliate::STATUS];

        return $status;
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Reconciliate::TXNDATE] ?? null;
    }

    protected function getArn($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NUMBER] ?? null;
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
