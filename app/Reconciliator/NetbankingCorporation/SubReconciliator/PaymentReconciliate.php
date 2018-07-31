<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Corporation\Status;
use RZP\Gateway\Netbanking\Corporation\ReconcilationFields;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    public function __construct(string $gateway = null)
    {
        parent::__construct($gateway);

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId(array $row)
    {
        return $row[ReconcilationFields::MERCHANT_TXN_ID];
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->netbankingRepo->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getReferenceNumber($row)
    {
        return $row[ReconcilationFields::BANK_TXN_ID];
    }

    protected function getCustomerDetails($row)
    {
        return [];
    }
}
