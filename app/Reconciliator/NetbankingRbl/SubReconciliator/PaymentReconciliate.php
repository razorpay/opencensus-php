<?php

namespace RZP\Reconciliator\NetbankingRbl;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Rbl\Status;
use RZP\Gateway\Netbanking\Rbl\ClaimFields;
use RZP\Models\Payment\Status as PaymentStatus;
use RZP\Models\Payment\Service as PaymentService;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    protected $netbankingRepo;

    public function __construct()
    {
        parent::__construct();

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId($row)
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

    protected function getGatewayPayment($paymentId)
    {
        $status = [Status::SUCCESS];

        return $this->netbankingRepo->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $status);
    }

    protected function getCustomerDetails($row)
    {
        return [
            Base\Reconciliate::CUSTOMER_ID   => $this->getNbCustomerId($row),
        ];
    }

    protected function getNbAccountDetails($row)
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

    protected function shouldAttemptForceAuthorizeFailed()
    {
        return true;
    }
}
