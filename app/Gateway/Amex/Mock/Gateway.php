<?php

namespace RZP\Gateway\Amex\Mock;

use RZP\Exception;
use RZP\Gateway\Amex;
use RZP\Gateway\Base;

class Gateway extends Amex\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
