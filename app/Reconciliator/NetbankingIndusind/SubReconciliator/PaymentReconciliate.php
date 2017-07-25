<?php

namespace RZP\Reconciliator\NetbankingIndusind;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Rbl\Constants;

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
        if (empty($row['PRN']) === false)
        {
            return $row['PRN'];
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
}
