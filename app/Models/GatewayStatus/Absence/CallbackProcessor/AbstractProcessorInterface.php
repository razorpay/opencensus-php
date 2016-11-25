<?php

namespace RZP\Models\GatewayStatus\Absence\CallbackProcessor;

interface AbstractProcessorInterface
{
    public function process(array $input);
}