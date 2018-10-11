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
        $this->setS2sFlowFlag($input);

        if (($this->s2sFlowFlag === false) or
            ($this->isFirstRecurringPayment($input) === true))
        {
            return $this->authorizeMock($input);
        }
        else
        {
            return parent::authorize($input);
        }
    }
}
