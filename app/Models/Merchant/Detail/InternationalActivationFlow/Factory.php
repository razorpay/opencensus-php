<?php

namespace RZP\Models\Merchant\Detail\InternationalActivationFlow;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;

class Factory
{
    const ACTIVATION_FLOW_IMPLEMENTATION_MAPPING = [
        InternationalActivationFlow::WHITELIST => Whitelist::class,
        InternationalActivationFlow::BLACKLIST => Blacklist::class,
        InternationalActivationFlow::GREYLIST  => Greylist::class,
    ];


    /**
     * @param Merchant\Entity $merchant
     *
     * @return ActivationFlowInterface
     * @throws LogicException
     */
    public static function getActivationFlowImpl(Merchant\Entity $merchant): ActivationFlowInterface
    {
        $merchantDetail = (new Detail\Core)->getMerchantDetails($merchant);

        $internationalActivationFlow = $merchantDetail->getInternationalActivationFlow();

        if (isset(self::ACTIVATION_FLOW_IMPLEMENTATION_MAPPING[$internationalActivationFlow]) === true)
        {
            $class = self::ACTIVATION_FLOW_IMPLEMENTATION_MAPPING[$internationalActivationFlow];

            return new $class($merchant);
        }

        throw new LogicException(
            ErrorCode::INVALID_ARGUMENT_INVALID_INTERNATIONAL_ACTIVATION_FLOW,
            null,
            [Detail\Entity::INTERNATIONAL_ACTIVATION_FLOW => $internationalActivationFlow]);
    }
}
