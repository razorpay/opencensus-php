<?php


namespace RZP\Models\AuthzAdmin;

use App;
use RZP\Models\Base;
use RZP\Models\Roles;
use RZP\Models\User\BankingRole;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;
use AuthzAdmin\Client\ApiException;
use AuthzAdmin\Client\Model\V1Role;
use AuthzAdmin\Client\Model\V1MigrateRole;
use AuthzAdmin\Client\Model\V1PrivilegeRoleMapping;
use AuthzAdmin\Client\Model\V1PrivilegeRoleMappingMetadata;
use AuthzAdmin\Client\Model\V1RolePolicyType;
use AuthzAdmin\Client\Model\V1MigrateRoleRequest;
use AuthzAdmin\Client\Model\V1Privilege;

class Service extends Base\Service
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed
     */
    private $authzXPlatformAdminClient;

    private $serviceId = '';

    private $config;

    private $baseUrl = '';

    /**
     * Service constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->authzXPlatformAdminClient = app('authzXPlatformAdmin');

        $this->config = app('config')->get('applications.authzXPlatformAdmin');

        $this->serviceId = $this->config['service_id'];

        $this->baseUrl = $this->config['url'];
    }

    public function adminAPIListPolicy(array $roles, string $merchantId = null, bool $enrichRoles = null)
    {
        $this->trace->info(TraceCode::AUTHZ_POLICY_LIST_REQUEST, [
            'roles'      => $roles
        ]);

        $response = [];

        $paginationToken = "*";

        $startTimeMs = round(microtime(true) * 1000);

        $fetchListPolicy = true;

        while ($fetchListPolicy)
        {
            if ($enrichRoles === true)
            {
                $policyItemsAndCount = $this->authzXPlatformAdminClient->adminAPIListPolicy(
                    $paginationToken,
                    $resourceGroupIdList = null,
                    $resourceIdList = null,
                    $roleId = null,
                    $serviceIdList = $this->serviceId,
                    $permissionIdList = null,
                    $roleNames = null,
                    $orgId = Constants::ORG_ID,
                    $role_owner_id = $merchantId,
                    $enrich_roles = true,
                    $role_ids = null,
                    $role_identifier = $roles[0]
                );
            }
            else
            {
                $policyItemsAndCount = $this->authzXPlatformAdminClient->adminAPIListPolicy(
                    $paginationToken,
                    $resourceGroupIdList = null,
                    $resourceIdList = null,
                    $roleId = null,
                    $serviceIdList = $this->serviceId,
                    $permissionIdList = null,
                    $roleNames = $roles,
                    $orgId = Constants::ORG_ID
                );
            }

            $paginationToken = $policyItemsAndCount->getPaginationToken();

            $policyList = array_map(function ($item)
            {
                // prefer permission.group if set, else make use of policy.name
                if (isset($item[Constants::PERMISSION]) && !empty($item[Constants::PERMISSION][Constants::GROUP]))
                {
                    return $item[Constants::PERMISSION][Constants::GROUP];
                }

                return $item->getName();
            }, $policyItemsAndCount->getItems());

            if (empty($paginationToken) === true || ($policyItemsAndCount->getCount() < Constants::PAGE_SIZE) ) {
                $fetchListPolicy = false;
            }

            $response = array_merge($response, array_unique($policyList));
        }

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::AUTHZ_POLICY_LIST_RESPONSE, [
            'authz_list_policy_res'                    => $response,
            'authz_request_time_elapsed_ms' => $totalFetchTime
        ]);

        return array_values(array_unique($response));
    }

    public function adminAPICreateRole(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_CREATE_ROLE_REQUEST, [
            'input'      => $input
        ]);

        $request = $this->convertAPIRoleToAuthzRole($input);

        $response = $this->authzXPlatformAdminClient->adminAPICreateRole($request, $this->getPassportHeader());

        $this->trace->info(TraceCode::AUTHZ_CREATE_ROLE_RESPONSE, [
            'response'   => $response
        ]);

        return $this->convertAuthzRoleToAPIRole($response);
    }

    public function adminAPIUpdateRole(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_UPDATE_ROLE_REQUEST, [
            'input'      => $input
        ]);

        $request = $this->convertAPIRoleToAuthzRole($input);

        $response = $this->authzXPlatformAdminClient->adminAPIUpdateRole($request, $this->getPassportHeader());

        $this->trace->info(TraceCode::AUTHZ_UPDATE_ROLE_RESPONSE, [
            'response'   => $response
        ]);

        return $this->convertAuthzRoleToAPIRole($response);
    }

    public function adminAPIMigrateRole(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_MIGRATE_ROLE_REQUEST, [
            'input'      => $input
        ]);

        $request = $this->prepareMigrateRoleRequest($input);

        try
        {
            $response = $this->authzXPlatformAdminClient->adminAPIMigrateRole($request);

            $this->trace->info(TraceCode::AUTHZ_MIGRATE_ROLE_RESPONSE, [
                'response' => $response,
            ]);
        }
        catch(\Exception $e)
        {
            $this->trace->error(TraceCode::AUTHZ_MIGRATE_ROLES_FAILED, [
                'error' => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'error'     => $e->getMessage()
            ];
        }

        return [
            'success'   => true
        ];
    }

    public function adminAPIListRole(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_LIST_ROLE_REQUEST, [
            'input'    => $input
        ]);

        $response = [];

        $paginationToken = "*";

        $startTimeMs = round(microtime(true) * 1000);

        $fetchListRole = true;

        $name = $input[Roles\Entity::NAME] ?? null;

        $names = $input[Roles\Constants::NAMES] ?? null;

        $type = $input[Roles\Entity::TYPE] ?? null;

        $types = null;

        if (isset($input[Roles\Constants::TYPES]))
        {
            $types = [];

            foreach($input[Roles\Constants::TYPES] as $t)
            {
                $types[] = $this->getAuthzRoleTypeString($t);
            }
        }

        $ownerIds = $input[Roles\Constants::OWNER_IDS] ?? null;

        $roleIds = $input[Roles\Constants::ROLE_IDS] ?? null;

        while ($fetchListRole)
        {
            $roleItemsAndCount = $this->authzXPlatformAdminClient->adminAPIListRole(
                $paginationToken,
                $roleNamePrefix = null,
                $roleNames = $names ?? $name, // prefer names attribute over name
                $roleIds,
                $orgId = Constants::ORG_ID,
                $keyId = null,
                $keyOwnerType = null,
                $keyOwnerId = null,
                $ownerIds,
                $type,
                $types
            );

            $paginationToken = $roleItemsAndCount->getPaginationToken();

            if (empty($paginationToken) === true || ($roleItemsAndCount->getCount() < Constants::PAGE_SIZE) )
            {
                $fetchListRole = false;
            }

            $response = array_merge($response, array_unique($roleItemsAndCount->getItems() ?? []));
        }

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::AUTHZ_LIST_ROLE_RESPONSE, [
            'authz_list_role_res'           => $response,
            'authz_request_time_elapsed_ms' => $totalFetchTime
        ]);

        $roles = [];

        foreach($response as $role)
        {
            $roles[] = $this->convertAuthzRoleToAPIRole($role);
        }

        return $roles;
    }

    public function adminAPIGetRole(string $roleId, string|null $ownerId, bool $expandChildren = false)
    {
        if (BankingRole::isCACStandardRole($roleId))
        {
            $roleId = BankingRole::getStandardRoleNameFromRoleId($roleId);
        }

        $this->trace->info(TraceCode::AUTHZ_GET_ROLE_REQUEST, [
            'role_id'      => $roleId
        ]);

        try
        {
            $startTimeMs = round(microtime(true) * 1000);

            $response = $this->authzXPlatformAdminClient->adminAPIGetRole($roleId, Constants::ORG_ID, $ownerId, $expandChildren);

            $endTime = round(microtime(true) * 1000);

            $totalFetchTime = $endTime - $startTimeMs;

            $this->trace->info(TraceCode::AUTHZ_GET_ROLE_RESPONSE, [
                'authz_get_role_res'                => $response,
                'authz_request_time_elapsed_ms'     => $totalFetchTime
            ]);

            $this->trace->histogram(Metrics::AUTHZ_GET_ROLE_DURATION_SECONDS, $totalFetchTime/1000, [
                Roles\Entity::ID => $roleId
            ]);
        }
        catch(ApiException $e)
        {
            if ($e->getCode() === 404 && strpos($e->getMessage(), Constants::SQL_NO_ROWS) !== false)
            {
                // this is done to retain the same expectations at all callers who need role/authz_roles
                return [];
            }

            throw $e;
        }

        $apiRole = $this->convertAuthzRoleToAPIRole($response);

        if ($expandChildren && !empty($response->getChildren()))
        {
            $childNames = array_map(function ($item) {
                return $item[Roles\Entity::NAME];
            }, $response->getChildren());

            $apiRole[Constants::CHILD_NAMES] = $childNames;
        }

        return $apiRole;
    }

    public function adminAPIListPrivileges(bool $expandActions)
    {
        $this->trace->info(TraceCode::AUTHZ_LIST_PRIVILEGES_REQUEST, [
            'expand_actions' => $expandActions
        ]);

        $privileges = [];

        $paginationToken = "*";

        $startTimeMs = round(microtime(true) * 1000);

        $fetchListPrivileges = true;

        while ($fetchListPrivileges)
        {
            $privilegesAndCount = $this->authzXPlatformAdminClient->adminAPIListPrivileges(
                $orgId = Constants::ORG_ID,
                $expandActions,
                $expandRoles = false,
                $paginationToken,
                $visibility = 1
            );

            $paginationToken = $privilegesAndCount->getPaginationToken();

            foreach ($privilegesAndCount->getItems() as $privilege)
            {
                $privileges[] = $this->convertAuthzPrivilegeToApiPrivilege($privilege);
            }

            if (empty($paginationToken) === true || ($privilegesAndCount->getCount() < Constants::PAGE_SIZE) )
            {
                $fetchListPrivileges = false;
            }
        }

        $endTimeMs = round(microtime(true) * 1000);

        $this->trace->info(TraceCode::AUTHZ_LIST_PRIVILEGES_RESPONSE, [
            'authz_list_privileges_res'     => $privileges,
            'authz_request_time_elapsed_ms' => $endTimeMs - $startTimeMs
        ]);

        $response[Constants::ITEMS] = $privileges;

        return $response;
    }

    public function adminAPICreatePrivilege(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_CREATE_PRIVILEGE_REQUEST, [
            'input'      => $input
        ]);

        $request = $this->convertAPIPrivilegeToAuthzPrivilege($input, false);

        $response = $this->authzXPlatformAdminClient->adminAPICreatePrivilege($request);

        $this->trace->info(TraceCode::AUTHZ_CREATE_PRIVILEGE_RESPONSE, [
            'response'      => $response
        ]);

        return $this->convertAuthzPrivilegeToApiPrivilege($response);
    }

    public function adminAPIUpdatePrivilege(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_UPDATE_PRIVILEGE_REQUEST, [
            'input'      => $input
        ]);

        $request = $this->convertAPIPrivilegeToAuthzPrivilege($input, true);

        $response = $this->authzXPlatformAdminClient->adminAPIUpdatePrivilege($request);

        $this->trace->info(TraceCode::AUTHZ_UPDATE_PRIVILEGE_RESPONSE, [
            'response'      => $response
        ]);

        return $this->convertAuthzPrivilegeToApiPrivilege($response);
    }

    public function adminAPICreatePrivilegeRoleMapping(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_CREATE_PRIVILEGE_ROLE_MAPPING_REQUEST, [
            'input'      => $input
        ]);

        $request = $this->convertAPIPRMToAuthzPRM($input, false);

        $response = $this->authzXPlatformAdminClient->adminAPICreatePrivilegeRoleMapping($request);

        $this->trace->info(TraceCode::AUTHZ_CREATE_PRIVILEGE_ROLE_MAPPING_RESPONSE, [
            'response'      => $response
        ]);

        return $this->convertAuthzPRMToAPIPRM($response);
    }

    public function adminAPIUpdatePrivilegeRoleMapping(array $input)
    {
        $this->trace->info(TraceCode::AUTHZ_UPDATE_PRIVILEGE_ROLE_MAPPING_REQUEST, [
            'input'      => $input
        ]);

        $request = $this->convertAPIPRMToAuthzPRM($input, true);

        $response = $this->authzXPlatformAdminClient->adminAPIUpdatePrivilegeRoleMapping($request);

        $this->trace->info(TraceCode::AUTHZ_UPDATE_PRIVILEGE_ROLE_MAPPING_RESPONSE, [
            'response'      => $response
        ]);

        return $this->convertAuthzPRMToAPIPRM($response);
    }

    private function prepareMigrateRoleRequest(array $input)
    {
        $res = new V1MigrateRoleRequest();

        $roles = [];

        foreach ($input['roles'] as $role)
        {
            $roleObj = new V1MigrateRole([
                'id'            => $role['id'],
                'name'          => $role['name'],
                'type'          => $this->getAuthzRoleType($role['type']),
                'owner_type'    => $role['owner_type'],
                'owner_id'      => $role['owner_id'],
                'child_names'   => $role['child_names'],
                'created_by'    => $role['created_by'],
                'description'   => $role['description'],
                'updated_by'    => $role['updated_by']
            ]);

            if ($role[Roles\Entity::TYPE] === Roles\Entity::STANDARD)
            {
                $roleObj->setOwnerId(strtolower($role['owner_id']));

                $roleObj->setId(UniqueIdEntity::generateUniqueId());
            }

            $roles[] = $roleObj;
        }

        $res->setRoles($roles);

        $res->setOrgId(Constants::ORG_ID);

        return $res;
    }

    private function getAuthzRoleType(string $type)
    {
        return $type === Roles\Entity::CUSTOM ? V1RolePolicyType::CUSTOM : V1RolePolicyType::STANDARD;
    }

    private function getAuthzRoleTypeString(string $type)
    {
        return $type === Roles\Entity::CUSTOM ? Constants::ROLE_POLICY_TYPE_CUSTOM : Constants::ROLE_POLICY_TYPE_STANDARD;
    }

    private function convertAPIRoleToAuthzRole(array $input): V1Role
    {
        return new V1Role([
            'id'            => $input['id'],
            'name'          => $input['name'],
            'org_id'        => Constants::ORG_ID,
            'type'          => $this->getAuthzRoleType($input['type']),
            'owner_type'    => Constants::MERCHANT,
            'owner_id'      => $input['merchant_id'],
            'child_ids'     => $input['child_ids'],
            'created_by'    => $input['created_by'],
            'description'   => $input['description'],
        ]);
    }

    private function convertAuthzRoleToAPIRole(V1Role $role): array
    {
        return [
            'id'           => $role->getType() === V1RolePolicyType::STANDARD ? BankingRole::getStandardRoleIdFromRoleName($role->getName()) : $role->getId(),
            'name'         => $role->getName(),
            'type'         => $role->getType() === V1RolePolicyType::STANDARD ? 'standard' : 'custom',
            'merchant_id'  => $role->getOwnerId(),
            'child_ids'    => $role->getChildIds(),
            'created_by'   => $role->getCreatedBy(),
            'description'  => $role->getDescription(),
        ];
    }

    private function convertAuthzPrivilegeToApiPrivilege(V1Privilege $privilege): array
    {
        $apiPrivilege = [
            'id'            => $privilege->getId(),
            'name'          => $privilege->getName(),
            'description'   => $privilege->getDescription(),
            'label'         => $privilege->getLabel(),
            'view_position' => $privilege->getViewPosition(),
            'parent_id'     => $privilege->getParentId(),
            'org_id'        => $privilege->getOrgId(),
            'visibility'    => $privilege->getVisibility(),
        ];

        $actions = [];

        foreach ($privilege->getActions() as $action)
        {
            $actions[$action->getAction()] = [
                'id'            => $action->getId(),
                'description'   => $action->getMetadata()->getDescription(),
                'label'         => $action->getMetadata()->getLabel(),
                'tooltip'       => $action->getMetadata()->getTooltip(),
                'privilege_id'  => $action->getPrivilegeId(),
                'role_ids'      => $action->getRoleIds(),
            ];
        }

        $apiPrivilege['actions'] = $actions;

        return $apiPrivilege;
    }

    private function convertAPIPrivilegeToAuthzPrivilege(array $input, bool $isUpdatePrivilege = false): V1Privilege
    {
        return new V1Privilege([
            'id'            => $isUpdatePrivilege ? $input['id'] : null,
            'name'          => $input['name'],
            'description'   => $input['description'],
            'label'         => $input['label'],
            'parent_id'     => $input['parent_id'],
            'view_position' => $input['view_position'],
            'org_id'        => Constants::ORG_ID,
            'visibility'    => $input['visibility'],
        ]);
    }

    private function convertAPIPRMToAuthzPRM(array $input, bool $isUpdatePRM = false): V1PrivilegeRoleMapping
    {
        $privilegeRoleMapping = new V1PrivilegeRoleMapping([
            'id'            => $isUpdatePRM ? $input['id'] : null,
            'action'        => $input['action'],
            'role_ids'      => $input['role_ids'],
            'org_id'        => Constants::ORG_ID,
            'privilege_id'  => $input['privilege_id'],
        ]);

        $metadata = $input['metadata'];

        $privilegeRoleMappingMetadata = new V1PrivilegeRoleMappingMetadata([
            'description'   => $metadata['description'],
            'label'         => $metadata['label'],
            'tooltip'       => $metadata['tooltip'],
        ]);

        $privilegeRoleMapping->setMetadata($privilegeRoleMappingMetadata);

        return $privilegeRoleMapping;
    }

    private function convertAuthzPRMToAPIPRM(V1PrivilegeRoleMapping $prm): array
    {
        $privilegeRoleMapping = [
            'id'            => $prm->getId(),
            'action'        => $prm->getAction(),
            'role_ids'      => $prm->getRoleIds(),
            'org_id'        => $prm->getOrgId(),
            'privilege_id'  => $prm->getPrivilegeId(),
        ];

        $privilegeRoleMapping['metadata'] = [
            'description'   => $prm->getMetadata()->getDescription(),
            'label'         => $prm->getMetadata()->getLabel(),
            'tooltip'       => $prm->getMetadata()->getTooltip(),
        ];

        return $privilegeRoleMapping;
    }

    private function getPassportHeader()
    {
        $app = App::getFacadeRoot();

        return $app['basicauth']->getPassportJwt($this->baseUrl);
    }
}
