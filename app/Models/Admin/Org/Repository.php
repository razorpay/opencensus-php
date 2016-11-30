<?php

namespace RZP\Models\Admin\Org;

use Carbon\Carbon;
use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'org';

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes|email',
        Entity::AUTH_TYPE             => 'sometimes|string|max:50',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
        Entity::HOSTNAME              => 'sometimes|string|max:100',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes|email',
        Entity::AUTH_TYPE             => 'sometimes|string|max:50',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
        Entity::HOSTNAME              => 'sometimes|string|max:100',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function findOrFailByHostname(string $hostname)
    {
        $hostname = mb_strtolower($hostname);

        return $this->newQuery()
                    ->where(Entity::HOSTNAME, '=', $hostname)
                    ->firstOrFailPublic();
    }
}
