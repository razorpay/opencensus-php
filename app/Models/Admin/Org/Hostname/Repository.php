<?php

namespace RZP\Models\Admin\Org\Hostname;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'org_hostname';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::ORG_ID                => 'sometimes|string',
        Entity::HOSTNAME              => 'sometimes|string|max:255',
    );

    public function findByHostname(string $hostname)
    {
        return $this->newQuery()
                    ->where(Entity::HOSTNAME, '=', $hostname)
                    ->first();
    }

    public function deleteHostnamesOfOrg(string $orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->delete();
    }
}