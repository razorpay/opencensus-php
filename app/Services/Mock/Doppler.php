<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment;
use RZP\Services\Doppler as BaseDoppler;

class Doppler extends BaseDoppler
{
    public function sendFeedback(Payment\Entity $payment, string $authorizeStatus, $errorCode = null, array $internalErrorDetails = [], $paymentRetryAttempt = null)
    {

    }

    public function sendRequest(string $method, string $path, string $content)
    {
        if($method === "GET")
        {
            return[
                [
                    "method" => "card",
                    "network" => "mastercard",
                    "high" => "10",
                    "medium" => "30",
                ],
                [
                    "method" => "upi",
                    "psp" => "paytm",
                    "high" => "5",
                    "medium" => "20",
                ]
            ];
        }
        else if($method === "PUT")
        {
            return[
                [
                    "method" => "upi",
                    "psp" => "paytm",
                    "high" => "5",
                    "medium" => "20",
                ]
            ];
        }

        return [];
    }
}
