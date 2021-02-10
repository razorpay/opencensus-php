<?php

namespace RZP\Reconciliator\NetbankingIndusind\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Indusind\Constants;
use RZP\Gateway\Netbanking\Indusind\ReconciliationFields;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [];

    protected function getPaymentId(array $row)
    {
        if (empty($row[ReconciliationFields::PAYMENT_ID]) === false)
        {
            return $row[ReconciliationFields::PAYMENT_ID];
        }

        return null;
    }

    const PII_COLUMNS = [
        ReconciliationFields::ACCOUNT_NUMBER,
    ];

    public function getGatewayPayment($paymentId)
    {
        $status = [Constants::YES];

        return $this->repo->netbanking->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $status);
    }

    protected function getAccountDetails($row)
    {
        return [
            Base\Reconciliate::ACCOUNT_NUMBER => $this->getDebitAccountNumber($row)
        ];
    }

    protected function getDebitAccountNumber($row)
    {
        if (empty($row[ReconciliationFields::ACCOUNT_NUMBER]) === false)
        {
            return $row[ReconciliationFields::ACCOUNT_NUMBER];
        }

        return null;
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = true;
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconciliationFields::PAYEE_ID] ?? null;
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
