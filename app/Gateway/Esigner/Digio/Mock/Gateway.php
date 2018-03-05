<?php

namespace RZP\Gateway\Esigner\Digio\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Esigner\Digio;

class Gateway extends Digio\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input, 'mock_esigner_payment');
    }
}
