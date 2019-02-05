<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;

class SubscriptionPaymentHandler extends Job
{
    protected $paymentData;

    protected $queueConfigKey = 'subscriptions_payment_notify';

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
