<?php

namespace RZP\Reconciliator\Kotak;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\Messenger;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_INT_PAYMENT_ID = 'int_payment_id';

    protected function getPaymentId($row)
    {
        $intPaymentId = $row[self::COLUMN_INT_PAYMENT_ID];

        $netbankingRepo = $this->repo->netbanking;

        $paymentId = $netbankingRepo->findByIntPaymentId($intPaymentId)->getPaymentId();

        return $paymentId;
    }
}
