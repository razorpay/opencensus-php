<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment;
use RZP\Services\Doppler as BaseDoppler;

class Doppler extends BaseDoppler
{
    public function sendFeedback(Payment\Entity $payment, string $paymentStatus, string $errorCode, string $internalErrorCode)
    {

    }
}
