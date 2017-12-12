<?php

namespace RZP\Gateway\Fss\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Fss;

class Gateway extends Fss\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
