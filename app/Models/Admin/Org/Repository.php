<?php

namespace RZP\Models\Admin\Org;

use Carbon\Carbon;
use RZP\Models\Admin\Org\Hostname;
use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'org';

    protected $merchantIdRequiredForMultipleFetch = false;

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes|email',
        Entity::AUTH_TYPE             => 'sometimes|string|max:50',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
        Entity::ALLOW_SIGN_UP         => 'sometimes|boolean',
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes|email',
        Entity::AUTH_TYPE             => 'sometimes|string|max:50',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
        Entity::ALLOW_SIGN_UP         => 'sometimes|boolean',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function findOrFailByHostname(string $hostname)
    {
        // Collect different table names, and their columns to query on
        $hostname = mb_strtolower($hostname);

        $orgId = $this->getAttributeWithTableName(Entity::ID);
        $orgColumnNames = $this->getAttributeWithTableName('*');

        $orgHostName = $this->manager->org_hostname;

        $orgHostnamesTable = $orgHostName->getTableName();

        $hostnameOrgId = $orgHostName->getAttributeWithTableName(Hostname\Entity::ORG_ID);
        $hostnameAttr = $orgHostName->getAttributeWithTableName(Hostname\Entity::HOSTNAME);

        // Join the orgs, and org_hostname table to get the org with the given hostname
        return $this->newQuery()
                    ->select($orgColumnNames)
                    ->join($orgHostnamesTable, $orgId, '=', $hostnameOrgId)
                    ->where($hostnameAttr, '=', $hostname)
                    ->firstOrFailPublic();
    }
}
