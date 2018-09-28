<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Error\ErrorCode;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Merchant\Detail\ActivationFlow as ActivationFlow;

class ActivationFlowFactory
{
    public function _construct(string $activationFlow) :ActivationFlowInterface
    {
        switch ($activationFlow)
        {
            case  ActivationFlow::WHITELIST:
                return new WhitelistActivationFlow();
            case ActivationFlow::BLACKLIST:
                return new BlacklistActivationFlow();
            case ActivationFlow::GREYLIST:
                return new GreylistActivationFlow();
            default:
                throw new InvalidArgumentException(ErrorCode::BAD_REQUEST_INVALID_ACTIVATION_FLOW);
        }
    }

}
