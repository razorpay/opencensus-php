<?php

namespace RZP\Reconciliator\Netbanking_Axis;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const COLUMN_PAYMENT_REF_NO = 'PRN No';

    protected function getPaymentId($row)
    {
        $prn = $row[self::COLUMN_PAYMENT_REF_NO];

        $paymentId = $this->repo->findByPaymentId($prn)->getPaymentId();

        return $paymentId;
    }
}
