<?php

namespace RZP\Reconciliator\NetbankingFederal;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Federal;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO  = 'PRN';
    const COLUMN_BANK_PAYMENT_ID = 'BID';
    const COLUMN_PAYMENT_DATE    = 'Date';

    protected $netbankingRepo;

    public function __construct()
    {
        parent::__construct();

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId($row)
    {
        if (isset($row[self::COLUMN_PAYMENT_REF_NO]) === true)
        {
            return $row[self::COLUMN_PAYMENT_REF_NO];
        }

        return null;
    }

    // protected function getReferenceNumber($row)
    // {
    //     if (isset($row[self::COLUMN_BANK_PAYMENT_ID]) === true)
    //     {
    //         return $row[self::COLUMN_BANK_PAYMENT_ID];
    //     }

    //     return null;
    // }

    protected function getGatewayPayment($paymentId)
    {
        $status = [Federal\Status::getAuthSuccessStatus()];

        return $this->netbankingRepo->findByPaymentIdActionAndStatus($paymentId,
                                                                     Action::AUTHORIZE,
                                                                     $status);
    }
}

