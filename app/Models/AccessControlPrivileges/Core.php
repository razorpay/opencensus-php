<?php

namespace RZP\Models\AccessControlPrivileges;;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\AccessPolicyAuthzRolesMap;
use RZP\Exception;
use RZP\Models\RoleAccessPolicyMap as RoleMap;
use RZP\Models\AuthzAdmin\Service as AuthzAdminService;


class Core extends Base\Core
{
    private $authzAdminService;

    public function __construct()
    {
        parent::__construct();

        $this->authzAdminService = new AuthzAdminService();
    }

    public function create(array $input) :array
    {
        $this->trace->info(TraceCode::ACCESS_CONTROL_PRIVILEGE_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $privilegeEntity = (new Entity)->build($input);

        if (empty($input['parent_id']) === false and $this->checkIfParentEntityExists($input['parent_id']) === false)
        {
            $this->trace->error(TraceCode::ACCESS_CONTROL_PRIVILEGE_INVALID_PARENT_ID,
                [
                    'input' => $input
                ]);

            throw new Exception\BadRequestValidationFailureException("Invalid Parent Id for input " ,
                 $input);
        }

        $this->repo->saveOrFail($privilegeEntity);

        $this->trace->info(TraceCode::ACCESS_CONTROL_PRIVILEGE_CREATE_RESPONSE,
            ['privilege_id' => $privilegeEntity->getId()]);

        return $privilegeEntity->toArrayPublic();

    }

    public function fetchPrivileges($input)
    {
        $privileges = $this->repo->access_control_privileges->fetchPrivileges($input);

        return $privileges;
    }

    public function generateResponseTemplate(& $privileges)
    {
        foreach ($privileges['items'] as & $privilege)
        {
            $privilege = $this->expandValueForKey($privilege, Entity::EXTRA_DATA);

            $actions = [];
            foreach ($privilege[Entity::ACTIONS]['items'] as $key => $actionData)
            {
                $action = $actionData[AccessPolicyAuthzRolesMap\Entity::ACTION];
                unset($actionData[AccessPolicyAuthzRolesMap\Entity::ACTION]);

                $actions[$action] = $this->expandValueForKey($actionData, AccessPolicyAuthzRolesMap\Entity::META_DATA);
            }

            $privilege[Entity::ACTIONS] = $actions;
        }

        $privileges = $this->makeHierarchicalStructure($privileges);
    }

    protected function expandValueForKey($array, $key)
    {
        if (empty($array[$key]) === true)
        {
            unset($array[$key]);
            return $array;
        }

        $data = $array[$key];
        unset($array[$key]);

        return array_merge($array, $data);
    }

    protected function makeHierarchicalStructure($privileges)
    {
        $items = [];
        array_walk($privileges['items'], function($value, $key) use (& $items){
            $items[$value[Entity::ID]] = $value;
            $items[$value[Entity::ID]][Entity::PRIVILEGE_DATA] = null;
        });

        foreach ($items as $id => $privilege)
        {
            if (empty($privilege[Entity::PARENT_ID]) === false)
            {
                $items[$privilege[Entity::PARENT_ID]][Entity::PRIVILEGE_DATA][] = $privilege;
                unset($items[$id]);
            }
        }

        $responseData = [];

        array_walk($items, function($value, $key) use (& $responseData){
            $responseData[] = $value;
        });

        return $responseData;
    }

    private function checkIfNameAlreadyExists(string $name) :bool
    {
        $tableName = Table::ACCESS_CONTROL_PRIVILEGES;

        $entity = $this->repo->{$tableName}->findByName($name);

        if (empty($entity) === true)
        {
            return false;
        }
        return true;
    }

    private function checkIfParentEntityExists(string $parentId) :bool
    {

        $tableName = Table::ACCESS_CONTROL_PRIVILEGES;

        $entity = $this->repo->{$tableName}->findById($parentId);

        if (empty($entity))
        {
            return false;
        }

        return true;
    }

    public function addNewPrivilegeAndItsDependencies(array $input) :array
    {
        $this->trace->info(TraceCode::CREATE_PRIVILEGE_AND_RELATED_DATA_REQUEST,
            [
                'input' => $input
            ]);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $privilegeEntity = $this->repo->transactionOnConnection(function() use ($input)
        {
            if (isset($input['privilege_id'])) {
                $privilegeId = $input['privilege_id'];

                $privilegeEntity = [];
            } else {
                //create privilge
                $privilegeData = $input['privilege'];

                $privilegeEntity = $this->create($privilegeData);

                $privilegeId = $privilegeEntity['id'];
            }

            //create access policies
            foreach ($input['accessPolicies'] as $accessPolicy)
            {
                $accessPolicyData = $accessPolicy['data'];

                if (isset($accessPolicy['access_policy_id'])) {
                    $accessPolicyId = $accessPolicy['access_policy_id'];

                    $accessPolicyEntity = "";
                } else {
                    $accessPolicyData[AccessPolicyAuthzRolesMap\Entity::PRIVILEGE_ID] = $privilegeId;

                    $accessPolicyEntity = (new AccessPolicyAuthzRolesMap\Service())->createMap($accessPolicyData);

                    $accessPolicyId = $accessPolicyEntity[AccessPolicyAuthzRolesMap\Entity::ID];
                }

                $authzRoles = $accessPolicyData[AccessPolicyAuthzRolesMap\Entity::AUTHZ_ROLES];

                //Update Role access policy map
                foreach ($accessPolicy['standardRolesApplicable'] as $roleId)
                {
                    $roleMap = $this->repo->role_access_policy_map->findOrFailByRoleId($roleId);

                    $newAccessPolicyIds = array_values(array_unique(array_merge($roleMap->getAccessPolicyIds(), [$accessPolicyId])));

                    $newAuthzRoles = array_values(array_unique(array_merge($roleMap->getAuthzRoles(), $authzRoles)));

                    $roleMapEditInput = [
                        RoleMap\Entity::ROLE_ID => $roleId,
                        RoleMap\Entity::ACCESS_POLICY_IDS => $newAccessPolicyIds,
                        RoleMap\Entity::AUTHZ_ROLES => $newAuthzRoles
                    ];

                    (new RoleMap\Service())->edit($roleMapEditInput);
                }
            }

            return $privilegeEntity;
        }, $mode);

        $this->trace->info(TraceCode::CREATE_PRIVILEGE_AND_RELATED_DATA_RESPONSE,
            [
                'privilege' => $privilegeEntity,
                // 'privilege_live' => $privilegeEntity
            ]);

        return $privilegeEntity;
    }

    public function fetchPrivilegesFromAuthz()
    {
        $privileges = $this->authzAdminService->adminAPIListPrivileges(true);

        array_multisort(array_column($privileges['items'], Entity::VIEW_POSITION), $privileges['items']);

        return [Entity::PRIVILEGE_DATA => $this->makeHierarchicalStructure($privileges)];
    }

    public function addPrivilegeOnAuthz(array $input)
    {
        return $this->authzAdminService->adminAPICreatePrivilege($input);
    }

    public function updatePrivilegeOnAuthz(array $input)
    {
        return $this->authzAdminService->adminAPIUpdatePrivilege($input);
    }

    public function addPrivilegeRoleMappingOnAuthz(array $input)
    {
        return $this->authzAdminService->adminAPICreatePrivilegeRoleMapping($input);
    }

    public function updatePrivilegeRoleMappingOnAuthz(array $input)
    {
        return $this->authzAdminService->adminAPIUpdatePrivilegeRoleMapping($input);
    }
}
