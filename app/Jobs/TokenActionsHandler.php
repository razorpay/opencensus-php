<?php
namespace RZP\Jobs;

class TokenActionsHandler extends Job
{
    protected $paymentData;

    protected $queueConfigKey = 'token_action_notify';

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
