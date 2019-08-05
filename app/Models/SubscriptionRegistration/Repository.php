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
                                         ->merchantId($merchantId)
                                         ->where(Entity::TOKEN_ID, '=', $tokenId)
                                         ->first();

        return $subscriptionRegistration;
    }

    // we are renaming this entity to Token.registration soon. Named the method with that in mind.
    public function getTokenRegistrationsForFirstCharge(array $merchantIds, int $count =100 )
    {
        $query = $this->newQueryWithoutTimestamps()
            ->where(Entity::STATUS, '=', Status::AUTHENTICATED)
            ->where(Entity::ATTEMPTS, '=', 0);

        if (empty($merchantIds) === false)
        {
            $merchantIdCol = $this->dbColumn(Entity::MERCHANT_ID);

            $query->whereIn($merchantIdCol, $merchantIds);
        }

        return $query->limit($count)->get();
    }
}
