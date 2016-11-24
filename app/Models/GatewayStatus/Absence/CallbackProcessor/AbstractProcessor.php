<?php

namespace RZP\Models\GatewayStatus\Absence\CallbackProcessor;


abstract class AbstractProcessor
{

    abstract public function process(array $input);

}