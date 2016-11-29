<?php

namespace RZP\Models\Admin\Org;

use Carbon\Carbon;
use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'org';

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes',
        Entity::AUTH_TYPE             => 'sometimes|string|max:500',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes',
        Entity::AUTH_TYPE             => 'sometimes|string|max:500',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function retrieveByEmail(string $email)
    {
        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->get();
    }

    public function findOrFailByHostname(string $hostname)
    {
        $hostname = mb_strtolower($hostname);

        return $this->newQuery()
                    ->where(Entity::HOSTNAME, '=', $hostname)
                    ->firstOrFailPublic();
    }
}
