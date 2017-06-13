<?php

namespace RZP\Models\Admin\Role;

use Config;

use RZP\Constants\Table;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;

class Repository extends Base\Repository
{
    protected $entity = 'role';

    protected $merchantIdRequiredForMultipleFetch = false;

    protected $proxyFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
    ];

    protected $appFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
    ];

    protected $adminFetchParamRules = [
        Entity::NAME    => 'sometimes|string',
        Entity::ORG_ID  => 'sometimes',
    ];

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

    public function findByOrgAndName(Org\Entity $org, string $name)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $org->getId())
                    ->where(Entity::NAME, '=', $name)
                    ->firstOrFailPublic();
    }

    public function getSuperAdminRoleByOrgId(string $orgId)
    {
        $name = Config::get('heimdall.default_role_name');

        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::NAME, '=', $name)
                    ->firstOrFailPublic();
    }

    public function getRolesForPermission(string $id, string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $pmMap = Table::PERMISSION_MAP;

        $rId = $this->dbColumn(Entity::ID);
        $rOrgId = $this->dbColumn(Entity::ORG_ID);

        return $this->newQuery()
                    ->join($pmMap, $rId, '=', $pmMap . '.entity_id')
                    ->where($pmMap . '.entity_type', '=', 'role')
                    ->where($pmMap . '.permission_id', '=', $id)
                    ->where($rOrgId, '=', $orgId)
                    ->get();
    }
}
