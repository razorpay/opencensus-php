<?php

namespace RZP\Gateway\AxisMigs\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Gateway\AxisMigs;
use RZP\Gateway\Base;

class Gateway extends AxisMigs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
