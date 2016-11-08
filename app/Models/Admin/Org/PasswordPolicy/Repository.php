<?php

namespace RZP\Models\Admin\Org\PasswordPolicy;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'password_policy';

    protected $proxyFetchParamRules = array(
        Entity::ORG_ID                => 'sometimes'
    );

    protected $appFetchParamRules = array(
        Entity::ORG_ID                => 'sometimes'
    );

    public function findByOrg($organisationId)
    {
        return $this->newQuery()
                    ->where(Org\Entity::ORG_ID, '=', $organisationId)
                    ->get();
    }
}
