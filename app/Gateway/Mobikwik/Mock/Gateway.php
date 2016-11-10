<?php

namespace RZP\Gateway\Mobikwik\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Mobikwik;

class Gateway extends Mobikwik\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
