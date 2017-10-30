<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Corporation\Status;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    public function __construct()
    {
        parent::__construct();

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId($row)
    {
        return $row[Constants::PAYMENT_ID];
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getReferenceNumber($row)
    {
        return $row[Constants::BANK_REF_ID];
    }

    protected function getCustomerDetails($row)
    {
        return [];
    }

    protected function getNbAccountDetails($row)
    {
        return [
            BaseReconciliate::ACCOUNT_NUMBER     => $row[Constants::ACCOUNT_NUMBER],
            BaseReconciliate::ACCOUNT_TYPE       => $row[Constants::ACCOUNT_TYPE],
            BaseReconciliate::ACCOUNT_SUBTYPE    => $row[Constants::ACCOUNT_SUB_TYPE],
            BaseReconciliate::ACCOUNT_BRANCHCODE => $row[Constants::BRANCH_CODE],
        ];
    }
}
