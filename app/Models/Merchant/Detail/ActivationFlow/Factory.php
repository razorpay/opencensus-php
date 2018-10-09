<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Detail\ActivationFlow as ActivationFlow;

class Factory
{
    const ACTIVATION_FLOW_IMPLEMENTATION_MAPPING = [
        ActivationFlow::WHITELIST => Whitelist::class,
        ActivationFlow::BLACKLIST => Blacklist::class,
        ActivationFlow::GREYLIST  => Greylist::class,
    ];

    /**
     * returns activationFlow interface implementation instance based on activation flow
     *
     * @param Entity $merchantDetails
     *
     * @return ActivationFlowInterface
     * @throws \RZP\Exception\LogicException
     */
    public static function getActivationFlowImpl(Entity $merchantDetails): ActivationFlowInterface
    {
        $activationFlow = $merchantDetails->getActivationFlow();

        if (isset(self::ACTIVATION_FLOW_IMPLEMENTATION_MAPPING[$activationFlow]) === true)
        {
            $class = self::ACTIVATION_FLOW_IMPLEMENTATION_MAPPING[$activationFlow];

            return new $class();
        }

        throw new LogicException(
            ErrorCode::INVALID_ARGUMENT_INVALID_ACTIVATION_FLOW,
            [Entity::ACTIVATION_FLOW => $activationFlow]);
    }
}
