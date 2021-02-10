<?php

namespace RZP\Reconciliator\NetbankingRbl\SubReconciliator;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Rbl\ClaimFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const COLUMN_PAYMENT_AMOUNT = 'Debit Amount';

    const BLACKLISTED_COLUMNS = [];

    const PII_COLUMNS = [
        ClaimFields::DEBIT_ACCOUNT,
        ClaimFields::CREDIT_ACCOUNT,
    ];

    protected function getPaymentId(array $row)
    {
        if (empty($row[ClaimFields::BANK_REFERENCE]) === false)
        {
            return $row[ClaimFields::BANK_REFERENCE];
        }

        return null;
    }

    protected function getReferenceNumber($row)
    {
        if (empty($row[ClaimFields::PGI_REFERENCE]) === false)
        {
            return $row[ClaimFields::PGI_REFERENCE];
        }

        return null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_ID   => $this->getNbCustomerId($row),
        ];
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $this->getDebitAccountNumber($row),
            Base\Reconciliate::CREDIT_ACCOUNT_NUMBER => $this->getCreditAccountNumber($row),
        ];
    }

    protected function getDebitAccountNumber($row)
    {
        if (empty($row[ClaimFields::DEBIT_ACCOUNT]) === false)
        {
            return $row[ClaimFields::DEBIT_ACCOUNT];
        }

        return null;
    }

    protected function getCreditAccountNumber($row)
    {
        if (empty($row[ClaimFields::CREDIT_ACCOUNT]) === false)
        {
            return $row[ClaimFields::CREDIT_ACCOUNT];
        }

        return null;
    }

    protected function getNbCustomerId($row)
    {
        if (empty($row[ClaimFields::USER_ID]) === false)
        {
            return $row[ClaimFields::USER_ID];
        }

        return null;
    }

    protected function getArn($row)
    {
        return $row[ClaimFields::PGI_REFERENCE] ?? null;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'acquirer' =>  [
                'reference1' => $this->getReferenceNumber($row),
            ]
        ];
    }
}
