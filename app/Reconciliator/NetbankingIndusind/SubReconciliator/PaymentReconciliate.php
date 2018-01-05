<?php

namespace RZP\Reconciliator\NetbankingIndusind;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Indusind\Constants;
use RZP\Gateway\Netbanking\Indusind\ReconciliationFields;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    protected $netbankingRepo;

    public function __construct()
    {
        parent::__construct();

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId(array $row)
    {
        if (empty($row[ReconciliationFields::PAYMENT_ID]) === false)
        {
            return $row[ReconciliationFields::PAYMENT_ID];
        }

        return null;
    }

    protected function getGatewayPayment($paymentId)
    {
        $status = [Constants::YES];

        return $this->netbankingRepo->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $status);
    }

    protected function getNbAccountDetails($row)
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

    protected function shouldAttemptForceAuthorizeFailed()
    {
        return true;
    }
}
