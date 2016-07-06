<?php

namespace RZP\Gateway\AxisGenius\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Gateway\AxisGenius;
use RZP\Gateway\Base;

class Gateway extends AxisGenius\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
