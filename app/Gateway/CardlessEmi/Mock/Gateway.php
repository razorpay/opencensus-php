<?php

namespace RZP\Gateway\CardlessEmi\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\CardlessEmi;

class Gateway extends CardlessEmi\Gateway
{
    use Base\Mock\GatewayTrait;

    public function __construct()
    {
        parent::__construct();

        $this->mock = true;
    }

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
