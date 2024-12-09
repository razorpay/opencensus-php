<?php

namespace RZP\Models\RoleAccessPolicyMap;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($input) :Entity
    {
        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $entity = (new Entity)->build($input);

        $this->repo->saveOrFail($entity);

        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_CREATE_RESPONSE,
            ['mapping_id' => $entity->getId()]);

        return $entity;
    }

    public function edit($input) :Entity
    {
        $roleMap = $this->repo->role_access_policy_map->findByRoleId($input['role_id']);

        if(empty($roleMap) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Request" ,
                $input);
        }

        $roleMap->edit($input);

        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_UPDATE_REQUEST,
            [
                'input' => $input
            ]);

        $this->repo->saveOrFail($roleMap);

        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_UPDATE_RESPONSE,
            ['mapping_id' => $roleMap->getId()]);

        return $roleMap;
    }

    public function fixRoleAccessPolicyMap(array $input)
    {
        $roleId = $input['role_id'];
        $dryRun = $input['dry_run'] ?? false;

        $this->trace->info(TraceCode::ROLE_ACCESS_POLICY_MAP_FIX_REQUEST,
                           [
                               'input' => $input
                           ]);

        $roleAccessPolicyMap = $this->repo->role_access_policy_map->findOrFailByRoleId($roleId);

        $roleAccessPolicyIds = $roleAccessPolicyMap->getAccessPolicyIds();
        $roleAccessPolicyIds = array_values(array_sort($roleAccessPolicyIds));

        $roleAuthzRoles = $roleAccessPolicyMap->getAuthzRoles();
        $roleAuthzRoles = array_values(array_sort($roleAuthzRoles));

        $accessPolicyAuthzRolesMapList = $this->repo->access_policy_authz_roles_map->findMany($roleAccessPolicyIds);

        $updatedAccessPolicyIds = [];
        $updatedAuthzRoles      = [];

        foreach ($accessPolicyAuthzRolesMapList as $accessPolicyMap)
        {
            $updatedAccessPolicyIds[] = $accessPolicyMap->getId();

            $authzRoles = $accessPolicyMap->getAuthzRoles();

            if (empty($authzRoles) === true)
            {
                continue;
            }
            $updatedAuthzRoles = array_merge($updatedAuthzRoles, $authzRoles);
        }

        $updatedAccessPolicyIds = array_values(array_sort(array_unique($updatedAccessPolicyIds)));
        $updatedAuthzRoles      = array_values(array_sort(array_unique($updatedAuthzRoles)));

        $accessPolicyIdsChanged = $roleAccessPolicyIds !== $updatedAccessPolicyIds;
        $authzRolesChanged      = $roleAuthzRoles !== $updatedAuthzRoles;

        $debugInfo              = [
            'old_access_policy_ids'     => $roleAccessPolicyIds,
            'old_authz_roles'           => $roleAuthzRoles,
            'new_access_policy_ids'     => $updatedAccessPolicyIds,
            'new_authz_roles'           => $updatedAuthzRoles,
            'access_policy_ids_changed' => $accessPolicyIdsChanged,
            'authz_roles_changed'       => $authzRolesChanged,
        ];
        $this->trace->info(TraceCode::FIX_ROLE_ACCESS_POLICY_MAP_DATA, $debugInfo);

        if ($dryRun === false and ($accessPolicyIdsChanged === true or $authzRolesChanged === true))
        {
            $roleMapEditInput = [
                Entity::ROLE_ID => $roleId,
            ];

            if ($accessPolicyIdsChanged)
            {
                $roleMapEditInput[Entity::ACCESS_POLICY_IDS] = $updatedAccessPolicyIds;
            }
            if ($authzRolesChanged)
            {
                $roleMapEditInput[Entity::AUTHZ_ROLES] = $updatedAuthzRoles;
            }

            $updatedRoleMap = $this->edit($roleMapEditInput);

            return [
                "debug_info"       => $debugInfo,
                'updated_role_map' => $updatedRoleMap->toArrayPublic(),
            ];
        }

        return [
            "debug_info" => $debugInfo,
            'role_map'   => $roleAccessPolicyMap->toArrayPublic(),
        ];
    }

    public function updateRoleAccessPolicyMap(array $input)
    {
        $this->trace->info(TraceCode::UPDATE_ROLE_ACCESS_POLICY_MAP_REQUEST, ['input' => $input]);

        $roleIds = $input[Constants::ROLE_IDS];

        $accessPolicyIds = $input[Entity::ACCESS_POLICY_IDS];

        $operation = $input[Constants::OPERATION];

        $authzRoles = $this->repo->access_policy_authz_roles_map->getAllAuthzRolesForAccessPolicyIds($accessPolicyIds);

        if (empty($authzRoles) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Access Policies" ,
                $input);
        }

        $roles = $this->repo->role_access_policy_map->findByRoleIds($roleIds);

        $failedRoleIds = [];

        /** @var Entity $role */
        foreach ($roles as $role)
        {
            try
            {
                $roleEdit = [
                    Entity::ROLE_ID             => $role->getRoleId(),
                    Entity::ACCESS_POLICY_IDS   => $this->resolveArrayForOperationType($role->getAccessPolicyIds(), $accessPolicyIds, $operation),
                    Entity::AUTHZ_ROLES         => $this->resolveArrayForOperationType($role->getAuthzRoles(), $authzRoles, $operation),
                ];

                $role->edit($roleEdit);

                $this->repo->saveOrFail($role);
            }
            catch (\Exception $e)
            {
                $failedRoleIds[] = $role->getId();

                $this->trace->error(TraceCode::ROLE_ACCESS_POLICY_MAP_UPDATE_FAILED,
                    [
                        'role_id'   => $role->getId(),
                        'error'     => $e->getMessage()
                    ]);
            }
        }

        return [
            'success'           => true,
            'failed_role_ids'   => $failedRoleIds,
        ];
    }

    private function resolveArrayForOperationType(array $existing, array $input, string $operationType)
    {
        switch($operationType)
        {
            case Constants::APPEND:
                return array_values(array_unique(array_merge($existing, $input)));
            case Constants::REMOVE:
                return array_values(array_unique(array_values(array_filter($existing, function($item) use ($input) {
                    return !in_array($item, $input);
                }))));
            default:
                throw new Exception\LogicException('Invalid role_access_policy_map update operation');
        }
    }
}
