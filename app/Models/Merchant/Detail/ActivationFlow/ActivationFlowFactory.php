<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Merchant\Detail\ActivationFlow as ActivationFlow;

class ActivationFlowFactory
{
    /**
     * returns activationFlow interface implementation instance based on activation flow
     * @param Entity $merchantDetails
     *
     * @return ActivationFlowInterface
     * @throws InvalidArgumentException
     */
    public function _construct(Entity $merchantDetails) :ActivationFlowInterface
    {
        switch ($merchantDetails->getActivationFlow())
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
