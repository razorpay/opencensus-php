<?php

namespace RZP\Models\Customer\Token;

use Config;

use RZP\Constants;
use RZP\Models\Base;

class ViewDataSerializer extends Base\Core
{

    protected $token;

    public function __construct(Entity $token)
    {
        parent::__construct();

        $this->token = $token;
    }

    public function serializeForSubscriptionRegistration()
    {
        $tokenData = $this->token->toArrayPublic();

        $tokenData[Constants\Entity::CUSTOMER] = $this->token->customer->toArrayPublic();

        $subscriptionRegistration = $this->repo->subscription_registration
                                                ->findByTokenIdAndMerchant(
                                                    $this->token->getId(),
                                                    $this->merchant->getId());

        if ($subscriptionRegistration !== null)
        {
            $tokenData[Constants\Entity::SUBSCRIPTION_REGISTRATION] = $subscriptionRegistration->toArrayPublic();
        }

        return $tokenData;
    }


}
