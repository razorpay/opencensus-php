<?php

namespace RZP\Models\Customer\Token;

use Config;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\UpiMandate;
use RZP\Models\SubscriptionRegistration\Method;

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

        if ($this->token->customer !== null)
        {
            $tokenData[Constants\Entity::CUSTOMER] = $this->token->customer->toArrayPublic();
        }

        $subscriptionRegistration = $this->repo->subscription_registration
                                               ->findByTokenIdAndMerchant(
                                                    $this->token->getId(),
                                                    $this->merchant->getId());

        if ($subscriptionRegistration !== null)
        {
            $tokenData[Constants\Entity::SUBSCRIPTION_REGISTRATION] = $subscriptionRegistration->toArrayPublic();

            if($subscriptionRegistration->getMethod() === Method::UPI)
            {
                $upimandate = $this->repo->upi_mandate->findByTokenId($this->token->getId());

                $tokenData
                [Constants\Entity::SUBSCRIPTION_REGISTRATION]
                [UpiMandate\Entity::FREQUENCY] = $upimandate->getFrequency();
            }
        }
        return $tokenData;
    }


}
