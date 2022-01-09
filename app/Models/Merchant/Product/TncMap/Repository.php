<?php

namespace RZP\Models\Merchant\Product\TncMap;

use RZP\Models\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'tnc_map';

    protected $proxyFetchParamRules = [
        Entity::ID           => 'sometimes|string|size:14',
        Entity::STATUS       => 'sometimes|string|in:active,inactive',
        Entity::PRODUCT_NAME => 'sometimes|string',
        Entity::BUSINESS_UNIT => 'sometimes|string'
    ];

    public function fetchLatestTnCByProductName(string $productName)
    {
        return $this->newQuery()
                    ->where(Entity::PRODUCT_NAME, '=', $productName)
                    ->where(Entity::STATUS, '=', 'active')
                    ->orderBy(Entity::UPDATED_AT, 'DESC')
                    ->first();
    }

    public function fetchLatestTnCByBusinessUnit(string $businessUnit)
    {
        return $this->newQuery()
            ->where(Entity::BUSINESS_UNIT, '=', $businessUnit)
            ->where(Entity::STATUS, '=', 'active')
            ->orderBy(Entity::UPDATED_AT, 'DESC')
            ->first();
    }
}
