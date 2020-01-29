<?php

namespace RZP\Models\Merchant\LegalEntity;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'legal_entity';

    protected $appFetchParamRules = [
        Entity::EXTERNAL_ID => 'sometimes|string',
    ];

    public function fetchByExternalId(string $externalId)
    {
        return $this->newQuery()
                    ->where(Entity::EXTERNAL_ID, $externalId)
                    ->first();
    }
}
