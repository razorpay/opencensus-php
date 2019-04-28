<?php

namespace RZP\Models\P2p\Upi;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * Class Processor
 */
class Processor extends Base\Processor
{
    public function gatewayCallback(array $input): array
    {
        $this->initializeApplicationTrait(Action::GATEWAY_CALLBACK, $input);

        $this->gatewayInput = $this->input;

        $this->callGateway();
    }

    public function gatewayCallbackSuccess(array $input): array
    {
        \mc::dd($input);
    }
}
