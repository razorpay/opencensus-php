<?php

namespace RZP\Gateway\Blade\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Blade;

class Gateway extends Blade\Gateway
{
    use Base\Mock\GatewayTrait;

    protected function validateSignatureAndInflatePares($pares)
    {
        //TODO : hack till this is fixed

        return $pares;
    }
}
