<?php

namespace RZP\Gateway\Aeps\Icici\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Aeps\Icici;

class Gateway extends Icici\Gateway
{
    use Base\Mock\GatewayTrait;

    // Not being used now
    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function getEncryptor(): Icici\Encryptor
    {
        return new Icici\Encryptor(2, $this->getIv(), true);
    }

}
