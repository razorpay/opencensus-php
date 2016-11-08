<?php

namespace RZP\Models\Admin\Org\AuthPolicy;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'auth_policy';

    protected $proxyFetchParamRules = array(
        Entity::ORG_ID => 'sometimes'
    );

    protected $appFetchParamRules = array(
        Entity::ORG_ID => 'sometimes'
    );

    public function findByOrg($organisationId)
    {
        return $this->newQuery()
                    ->where(Org\Entity::ORG_ID, '=', $organisationId)
                    ->find();
    }
}
