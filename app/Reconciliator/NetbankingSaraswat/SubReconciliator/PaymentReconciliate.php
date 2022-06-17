<?php

namespace RZP\Reconciliator\NetbankingSaraswat\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Reconciliator\NetbankingSaraswat\Reconciliate;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS   = [];

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
            Base\Reconciliate::ACCOUNT_NUMBER => $row[Reconciliate::ACCOUNT_NUMBER] ?? null
        ];
    }

    protected function getGatewayAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Reconciliate::AMT]);
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
            'acquirer'            =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
