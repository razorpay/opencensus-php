<?php

namespace RZP\Models\SubscriptionRegistration;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'subscription_registration';

    public function fetchTokensByMerchant($merchant, $input)
    {
        return $this->repo->token->fetchByMerchant($input, $merchant->getId());
    }
}