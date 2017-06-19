<?php

namespace RZP\Reconciliator\NetbankingRbl;

use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Rbl;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO  = 'Transaction';
    const COLUMN_BANK_PAYMENT_ID = 'Merchant Ref No.';
    const COLUMN_PAYMENT_DATE    = 'Transaction Date/Time';

    protected $netbankingRepo;

    public function __construct()
    {
        parent::__construct();

        $this->netbankingRepo = $this->repo->netbanking;
    }

    protected function getPaymentId($row)
    {
        if (empty($row[self::COLUMN_PAYMENT_REF_NO]) === false)
        {
            return $row[self::COLUMN_PAYMENT_REF_NO];
        }

        return null;
    }

    // protected function getReferenceNumber($row)
    // {
    //     if (empty($row[self::COLUMN_BANK_PAYMENT_ID]) === false)
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
