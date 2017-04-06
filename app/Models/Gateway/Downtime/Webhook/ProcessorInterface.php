<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

interface ProcessorInterface
{
    public function process(array $input);
}