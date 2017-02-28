<?php

namespace RZP\Gateway\FirstData\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\FirstData;

class Gateway extends FirstData\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
