<?php

namespace RZP\Reconciliator\BajajFinserv\SubReconciliator;

use RZP\Reconciliator\Base;;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    protected function getPaymentId(array $row)
    {
        return $row['asset_serial_numberimei'] ?? null;
    }
}
