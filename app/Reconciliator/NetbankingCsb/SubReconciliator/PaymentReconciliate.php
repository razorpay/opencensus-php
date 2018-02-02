<?php

namespace RZP\Reconciliator\NetbankingCsb;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const PAYMENT_ID      = 'payment_id';
    const STATUS          = 'status';
    
    protected function getPaymentId(array $row)
    {
        return $row[self::PAYMENT_ID];
    }

    protected function getReconPaymentStatus(array $row)
    {
        return $row[self::STATUS] ?? 'Y';
    }


}
