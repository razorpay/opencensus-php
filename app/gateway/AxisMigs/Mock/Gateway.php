<?php

namespace Gateway\AxisMigs\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\AxisMigs;
use Gateway\Base;

class Gateway extends AxisMigs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
