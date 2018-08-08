<?php

namespace RZP\Reconciliator\Bob;

use Carbon\Carbon;

use RZP\Reconciliator\Base;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    public function getPaymentId(array $row)
    {
        return $row[ReconcilationFields::MERCHANT_TRACK_ID];
    }
}
