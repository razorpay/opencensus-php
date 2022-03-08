<?php

namespace RZP\Reconciliator\NetbankingTmb\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingTmb\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];
    const COLUMN_PAYMENT_AMOUNT = Reconciliate::TRANSACTION_AMOUNT;

    const PII_COLUMNS = [
        Reconciliate::ACCOUNT_NUMBER,
    ];

    protected function getPaymentId(array $row)
    {
        return $row[Reconciliate::MERCHANT_TRN] ?? null;
    }

    protected function getReferenceNumber($row)
    {
        return $row[Reconciliate::BANK_REFERENCE_NO] ?? null;
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $row[Reconciliate::ACCOUNT_NUMBER] ?? null
        ];
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::TRANSACTION_AMOUNT]);
    }

    protected function getGatewayPaymentDate($row)
    {
        return $row[Reconciliate::TXN_DATE_TIME] ?? null;
    }

    protected function getArn($row)
    {
        return $this->getReferenceNumber($row);
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer'  =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
