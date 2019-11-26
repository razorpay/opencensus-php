<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment;
use RZP\Services\Doppler as BaseDoppler;

class Doppler extends BaseDoppler
{
    public function sendFeedback(Payment\Entity $payment, string $authorizeStatus, $errorCode = null, $internalErrorCode = null)
    {

    }

    public function sendRequest(string $method, string $path, string $content)
    {

    }
}
