<?php

namespace RZP\Gateway\Esigner\Legaldesk\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Esigner\Legaldesk;

class Gateway extends Legaldesk\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        // We don't need to put mock server URL here, since it's expected
        // from the gateway.
        return parent::authorize($input);
    }
}
