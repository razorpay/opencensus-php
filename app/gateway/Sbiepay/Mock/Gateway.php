<?php

namespace Gateway\Sbiepay\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;

class Gateway extends \Gateway\Sbiepay\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
