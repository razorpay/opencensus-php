<?php

namespace RZP\Models\Admin\Role;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'role';

    protected $proxyFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
    ];

    protected $appFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
    ];

    public function findByPublicIdAndOrgIdWithRelations(
        string $roleId,
        string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        Entity::verifyIdAndStripSign($roleId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->with('permissions')
                    ->findOrFailPublic($roleId);
    }

    public function fetchRolesForOrg($orgId)
    {
        return $this->newQuery()
                    ->orgId($orgId)
                    ->with('permissions')
                    ->get();
    }

    public function validateOrgHasNoSuchRole(Entity $role, Org\Entity $org)
    {
        $roleExists = $this->newQuery()
                           ->where(Entity::ORG_ID, '=', $org->getId())
                           ->where(Entity::NAME, '=', $role->getName())
                           ->exists();

        if ($roleExists === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The role with the name already exists');
        }
    }
}
