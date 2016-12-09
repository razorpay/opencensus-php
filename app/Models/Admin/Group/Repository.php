<?php

namespace RZP\Models\Admin\Group;

use RZP\Models\Admin\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Org;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'group';

    protected $merchantIdRequiredForMultipleFetch = false;

    // TODO Define the proxyfetch and admin fetch params

    protected $proxyFetchParamRules = [
        Entity::ORG_ID  => 'sometimes|string',
        Entity::NAME    => 'sometimes|string',
    ];

    public function validateOrgHasNoSuchGroup(Entity $group, Org\Entity $org)
    {
        $grpExists = $this->newQuery()
                          ->orgId($org->getId())
                          ->where(Entity::NAME, '=', $group->getName())
                          ->exists();

        if ($grpExists === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The group with the name already exists');
        }
    }
}
