<?php

namespace RZP\Gateway\Mpi\Enstage\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Mpi\Enstage;

class Gateway extends Enstage\Gateway
{
    use Base\Mock\GatewayTrait;

    protected $requestHash;

    public function otpGenerate(array $input)
    {
        return parent::otpGenerate($input);
    }
}
