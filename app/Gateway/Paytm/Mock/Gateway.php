<?php

namespace RZP\Gateway\Paytm\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Paytm;

class Gateway extends Paytm\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
