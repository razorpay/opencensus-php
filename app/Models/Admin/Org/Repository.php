<?php

namespace RZP\Models\Admin\Org;

use Carbon\Carbon;
use RZP\Constants\Table;
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
    );

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::EMAIL                 => 'sometimes|email',
        Entity::AUTH_TYPE             => 'sometimes|string|max:50',
        Entity::EMAIL_DOMAINS         => 'sometimes|string|max:500',
    );

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function findOrFailByHostname(string $hostname)
    {
        $hostname = mb_strtolower($hostname);

        $orgId = $this->getAttributeWithTableName(Entity::ID);
        $hostnameOrgId = $this->manager
                              ->org_hostname
                              ->getAttributeWithTableName(Hostname\Entity::ORG_ID);

        $hostnameAttr = $this->manager
                             ->org_hostname
                             ->getAttributeWithTableName(Hostname\Entity::HOSTNAME);

        $orgColumnNames = $this->getAttributeWithTableName('*');

        $orgHostnamesTable = $this->manager
                                  ->org_hostname
                                  ->getTableName();

        return $this->newQuery()
                    ->select($orgColumnNames)
                    ->join($orgHostnamesTable, $orgId, '=', $hostnameOrgId)
                    ->where($hostnameAttr, '=', $hostname)
                    ->firstOrFailPublic();
    }

    public function findOrFailWithHostname(string $orgId)
    {
        return $this->newQuery()
                    ->findOrFailPublic($orgId);
    }
}
