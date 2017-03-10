<?php

namespace RZP\Models\Gateway\Downtime\WebhookProcessor;

interface AbstractProcessorInterface
{
    public function process(array $input);
}