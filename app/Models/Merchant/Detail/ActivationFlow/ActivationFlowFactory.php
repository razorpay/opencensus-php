<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\Merchant\Detail\ActivationFlow as ActivationFlow;

class ActivationFlowFactory
{
    const ACTIVATION_FLOW_TO_CLASS_MAPPING = [
        ActivationFlow::WHITELIST => 'WhitelistActivationFlow',
        ActivationFlow::BLACKLIST => 'BlacklistActivationFlow',
        ActivationFlow::GREYLIST  => 'GreylistActivationFlow',
    ];

    /**
     * returns activationFlow interface implementation instance based on activation flow
     *
     * @param Entity $merchantDetails
     *
     * @return ActivationFlowInterface
     * @throws InvalidArgumentException
     */
    public function _construct(Entity $merchantDetails): ActivationFlowInterface
    {
        $activationFlow = $merchantDetails->getActivationFlow();

        if (isset(self::ACTIVATION_FLOW_TO_CLASS_MAPPING[$activationFlow]) === true)
        {
            $class = self::ACTIVATION_FLOW_TO_CLASS_MAPPING[$activationFlow];

            return new $class();
        }

        throw new InvalidArgumentException(ErrorCode::BAD_REQUEST_INVALID_ACTIVATION_FLOW);
    }

}
