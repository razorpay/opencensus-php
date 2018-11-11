<?php

namespace RZP\Gateway\Atom\Mock;

use RZP\Gateway\Atom;
use RZP\Gateway\Base;

class Gateway extends Atom\Gateway
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
