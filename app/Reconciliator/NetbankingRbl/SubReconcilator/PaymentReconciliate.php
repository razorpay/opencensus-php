<?php

namespace RZP\Reconciliator\NetbankingRbl;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Rbl\Status;
use RZP\Gateway\Netbanking\Rbl\ClaimFields;

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
}
