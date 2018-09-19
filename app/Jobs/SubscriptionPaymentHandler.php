<?php

namespace RZP\Jobs;

class SubscriptionPaymentHandler extends Job
{
    protected $paymentData;

    public function __construct(array $payload, string $mode)
    {
        parent::__construct($mode);

        $this->paymentData = $payload;
    }

    public function getPaymentData(): array
    {
        return $this->paymentData;
    }
}
