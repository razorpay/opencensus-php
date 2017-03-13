<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

interface AbstractProcessorInterface
{
    public function process(array $input);
}