<?php

namespace RZP\Gateway\AxisMigs\Mock;

use RZP\Exception;
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
