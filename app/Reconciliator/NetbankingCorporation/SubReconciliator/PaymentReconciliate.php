<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    protected function getPaymentId($row)
    {
        return $row[Constants::PAYMENT_ID];
    }
}
