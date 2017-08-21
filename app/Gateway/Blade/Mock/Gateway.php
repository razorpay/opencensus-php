<?php

namespace RZP\Gateway\Blade\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Blade;

class Gateway extends Blade\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
