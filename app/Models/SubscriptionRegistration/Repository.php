<?php

namespace RZP\Models\SubscriptionRegistration;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'subscription_registration';

    public function fetchRecurringTokensByMerchant($merchant, $input) : Base\PublicCollection
    {
        return $this->repo->token->fetchRecurringTokensByMerchant($input, $merchant->getId());
    }
}
