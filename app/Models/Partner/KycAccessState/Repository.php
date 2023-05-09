<?php

namespace RZP\Models\Partner\KycAccessState;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends  Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'partner_kyc_access_state';

    protected $adminFetchParamRules = [
        Entity::PARTNER_ID  => 'sometimes|string|size:14',
    ];

    public function findByPartnerIdAndEntityId(string $partnerId, string $subMerchantId): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $subMerchantId)
                    ->where(Entity::PARTNER_ID, $partnerId)
                    ->get();
    }

    public function findByPartnerIdAndEntityIds(string $partnerId, array $subMerchantIds): Base\PublicCollection
    {
        return $this->newQuery()
                    ->whereIn(Entity::ENTITY_ID, $subMerchantIds)
                    ->where(Entity::PARTNER_ID, $partnerId)
                    ->get();
    }

    public function findByPartnerIdAndEntityIdAndToken(string $partnerId, string $subMerchantId, string $tokenType, string $token): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $subMerchantId)
                    ->where(Entity::PARTNER_ID, $partnerId)
                    ->where($tokenType, $token)
                    ->get();
    }
}
