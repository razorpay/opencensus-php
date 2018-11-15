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

    public function findByTokenIdAndMerchant(string $tokenId, string $merchantId)
    {
        $subscriptionRegistration = $this->newQuery()
                      ->where(Entity::MERCHANT_ID, '=', $merchantId)
                      ->where(Entity::TOKEN_ID, '=', $tokenId)
                      ->first();

        return $subscriptionRegistration;
    }
}
