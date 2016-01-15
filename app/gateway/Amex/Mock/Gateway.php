<?php

namespace Gateway\Amex\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Amex;
use Gateway\Base;

class Gateway extends Amex\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
