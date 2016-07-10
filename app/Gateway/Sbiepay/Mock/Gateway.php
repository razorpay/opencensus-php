<?php

namespace RZP\Gateway\Sbiepay\Mock;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base;

class Gateway extends RZP\Gateway\Sbiepay\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
