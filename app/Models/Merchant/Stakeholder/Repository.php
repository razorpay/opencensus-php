<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'stakeholder';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
    ];

    public function fetchStakeholders(string $merchantId): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }
}
