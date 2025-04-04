<?php

namespace RZP\Models\Roles;

use Cache;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use Throwable;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin\Role as AdminRole;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\User\BankingRole;
use RZP\Models\RoleAccessPolicyMap;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Merchant\RazorxTreatment;
use AuthzAdmin\Client\Model\V1RolePolicyType;

class Service extends Base\Service
{
    protected $validator;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->validator = new Validator;
    }

    public function listRolesForMerchant($input)
    {
        $this->validator->validateInput('view', $input);

        $rolesGrouppedByType = $this->core->listRolesForMerchant($input);

        $rolesGrouppedByType[Entity::STANDARD] = $this->core->filterFinanceRoleForMerchant($this->merchant->getId(), $rolesGrouppedByType[Entity::STANDARD]);

        if (empty($rolesGrouppedByType[Entity::CUSTOM]) === false)
        {
            array_multisort(array_column($rolesGrouppedByType[Entity::CUSTOM], Entity::NAME), $rolesGrouppedByType[Entity::CUSTOM]);
        }

        $order = Entity::$displayOrder;

        $is = usort($rolesGrouppedByType[Entity::STANDARD], function ($a, $b) use ($order) {
            $pos_a = array_search($a['id'], $order);
            $pos_b = array_search($b['id'], $order);
            return $pos_a - $pos_b;
        });

        return $rolesGrouppedByType;
    }

    public function listRolesMap($input)
    {
        Entity::$rolesHiddenFromDashboard = [];

        return $this->listRolesForMerchant($input);
    }

    public function listRolesMapForAdmin($input)
    {
        $isCacEnabled = $this->merchant->isCACEnabled();

        if ($isCacEnabled === true)
        {
            $this->core->setInputParamForListRoles($input);

            $roles = $this->core->listRoles($input);

            $roles['items'] = $this->core->filterFinanceRoleForMerchant($this->merchant->getId(), $roles['items']);

            return $roles['items'];
        }
        else
        {
            $orgId = $this->app['basicauth']->getAdminOrgId();

            $roleNames = [
                'Finance L1',
                'Finance L2',
                'Finance L3',
                'Finance',
                'Owner',
                'Admin',
            ];

            $roles = (new AdminRole\Repository())->fetchRolesByOrgIdNames($orgId, $roleNames);

            return $roles->toArray();
        }
    }

    public function fetchSelfRole()
    {
        $id = 'role_'.$this->app['basicauth']->getUserRole();

        return $this->fetch($id, []);
    }

    public function fetch(string $id, array $input): array
    {
        $input['expand'] = [Entity::ACCESS_POLICY];

        Entity::stripRoleId($id);

        $merchantId = $this->merchant->getId();

        if ($this->merchant->checkCACMigrationExperimentEnabled())
        {
            $role = (new \RZP\Models\AuthzAdmin\Service())->adminAPIGetRole($id, $merchantId, false);

            if (empty($role))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'The id provided does not exist');
            }

            // get user count for this role for merchant
            $role[Entity::MEMBERS] = $this->repo->merchant_user->getBankingUserCountByMerchantIdAndRoleIds($merchantId, [$role[Entity::ID]]);

            return $role;
        }

        $roles = $this->repo->roles->findOrFailByPublicIdWithParams($id, $input)->toArrayPublicWithExpand();

        $roles[RoleAccessPolicyMap\Entity::ACCESS_POLICY_IDS] =
            $roles[Entity::ACCESS_POLICY][RoleAccessPolicyMap\Entity::ACCESS_POLICY_IDS] ?: null;

        unset($roles[Entity::ACCESS_POLICY]);

        // get user count for this role for merchant
        $roles[Entity::MEMBERS] = $this->repo->merchant_user->getBankingUserCountByMerchantIdAndRoleIds($merchantId, [$roles[Entity::ID]]);

        return $roles;
    }

    public function create(array $input) :array
    {
        if ($this->merchant->checkCACMigrationExperimentEnabled())
        {
            (new Validator())->validateInput(Validator::CREATE_ON_AUTHZ, $input);

            return $this->core->createOnAuthz($input);
        }

        $accessPolicyIds = array_pull($input, 'access_policy_ids');

        if(empty($accessPolicyIds) === true or gettype($accessPolicyIds) != "array")
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Access Policies" ,
                $input);
        }

        // check if the access policies are valid
        if((new \RZP\Models\AccessPolicyAuthzRolesMap\Core())
            ->checkIfAllAccessPolicyIdsExists($accessPolicyIds) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Access Policies" ,
                $input);
        }

        $role = $this->core->create($input, $accessPolicyIds);

        return $role->toArrayPublic();
    }

    public function edit(string $id, array $input) :array
    {
        $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_UPDATE_REQUEST,
            [
                'input' => $input
            ]);

        Entity::stripRoleId($id);

        if (\RZP\Models\User\BankingRole::isCACStandardRole($id) === false)
        {
            Cache::forget(Constants::CACHE_KEY_ROLE_NAME.$id);
        }

        if ($this->merchant->checkCACMigrationExperimentEnabled())
        {
            (new Validator())->validateInput(Validator::UPDATE_ON_AUTHZ, $input);

            return $this->core->updateOnAuthz($id, $input);
        }

        $accessPolicyIds = array_pull($input, 'access_policy_ids');

        if(empty($accessPolicyIds) === true or gettype($accessPolicyIds) != "array")
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Access Policies" ,
                $input);
        }

        // check if the access policies are valid
        if((new \RZP\Models\AccessPolicyAuthzRolesMap\Core())
                ->checkIfAllAccessPolicyIdsExists($accessPolicyIds) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Access Policies" ,
                $input);
        }

        $role = $this->core->edit($id, $input, $accessPolicyIds);

        $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_UPDATE_RESPONSE,
            ['role_id' => $role->getId()]);

        return $role->toArrayPublic();
    }

    public function createStandardRole(array $input)
    {
        $accessPolicyIds = $input['access_policy_ids'];

        unset($input['access_policy_ids']);

        $this->core->createStandardRole($input, $accessPolicyIds);
    }

    public function deleteRole(string $id) :array
    {
        $this->trace->info(TraceCode::ROLE_DELETE_REQUEST, [Entity::ROLE_ID => $id]);

        $role = $this->repo->roles->findOrFailByPublicIdWithParams($id);

        if(empty($role) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Role Id" ,
                [Entity::ROLE_ID => $id]);
        }

        if($role->getType() === Entity::STANDARD)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Standard roles cannot be deleted',
                [
                    'id'    => $id,
                ]);
        }

        //get users linked to this role
        $merchantId = $this->merchant->getId();

        if($this->repo->merchant_user->checkIfBankingMerchantUsersAreLinkedToRoleId($merchantId, $id) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Can't delete this role as users are mapped to this role",
                [
                    'id' => $id,
                    'merchantId' => $merchantId
                ]);

        }

        $this->core->delete($role);

        $this->trace->info(TraceCode::ROLE_DELETED, $role->toArray());

        return $role->toArrayPublic();
    }

    // Used to migrate data from API to Authz in case of CAC migration experiment scale up
    public function migrateToAuthz(array $input)
    {
        $this->trace->info(TraceCode::MIGRATE_AUTHZ_REQUEST, $input);

        $this->validator->validateInput(Validator::MIGRATE_AUTHZ, $input);

        return $this->core->migrateToAuthz($input[Constants::ROLE_IDS]);
    }

    // Used to migrate data from Authz to API in case of CAC migration experiment scale down
    public function migrateToApi(array $input)
    {
        $this->trace->info(TraceCode::MIGRATE_API_REQUEST, $input);

        foreach($input as $roleArray)
        {
            $this->validator->validateInput(Validator::MIGRATE_API, $roleArray);
        }

        return $this->core->migrateToApi($input);
    }

    public function getRoleNameUsingExperiment(string|null $roleId)
    {
        if (empty($roleId))
        {
            return null;
        }

        $cachedRoleName = Cache::get(Constants::CACHE_KEY_ROLE_NAME.$roleId);

        if (!empty($cachedRoleName))
        {
            return $cachedRoleName;
        }

        $role = $this->getRoleUsingExperiment($roleId);

        if (empty($role))
        {
            return null;
        }

        Cache::put(Constants::CACHE_KEY_ROLE_NAME.$roleId, $role->getName(), Constants::ROLE_NAME_TTL);

        return $role->getName();
    }

    public function getRoleUsingExperiment(string|null $roleId)
    {
        if (empty($roleId))
        {
            return null;
        }

        $roleLocation = $this->locateRoleUsingExperiment($roleId);

        if ($roleLocation[Constants::LOCATION] === Constants::API)
        {
            if (!empty($roleLocation[Constants::ROLE]))
            {
                return $roleLocation[Constants::ROLE];
            }

            return $this->repo->roles->fetchRole($roleId);
        }

        return $this->getRoleFromAuthz($roleId, $roleLocation[Entity::MERCHANT_ID]);
    }

    public function getAuthzRolesUsingExperiment(string|null $roleId)
    {
        if (empty($roleId))
        {
            return null;
        }

        $roleLocation = $this->locateRoleUsingExperiment($roleId);

        if ($roleLocation[Constants::LOCATION] === Constants::API)
        {
            $roleMap = $this->repo->role_access_policy_map->findByRoleId($roleId);

            if (empty($roleMap) === false)
            {
                return $roleMap->getAuthzRoles();
            }

            return [];
        }

        return $this->getAuthzRolesForRoleFromAuthz($roleId, $roleLocation[Entity::MERCHANT_ID]);
    }

    public function getRoleNamesUsingExperiment(array|null $roleIds)
    {
        if (empty($roleIds))
        {
            return [];
        }

        $roles = $this->getRolesUsingExperiment($roleIds);

        $roleNames = [];

        foreach($roles as $role)
        {
            $roleNames[$role[Entity::ID]] = $role[Entity::NAME];
        }

        return $roleNames;
    }

    public function getRolesUsingExperiment(array|null $roleIds)
    {
        if (empty($roleIds))
        {
            return [];
        }

        $standardRoleIds = [];

        $customRoleIds = [];

        $rolesData = [];

        // 1. filter out the standard roles & custom roles from $roleIds
        foreach($roleIds as $roleId)
        {
            if (BankingRole::isCACStandardRole($roleId))
            {
                $standardRoleIds[] = $roleId;
            }
            else
            {
                $customRoleIds[] = $roleId;
            }
        }

        // 2. make authz request to get details of standard roles
        $standardRoles = [];

        if (!empty($standardRoleIds))
        {
            $standardRoles = (new \RZP\Models\AuthzAdmin\Service())->adminAPIListRole([
                Constants::NAMES  => $standardRoleIds,
                Entity::TYPE      => V1RolePolicyType::STANDARD,
            ]);
        }

        // 3. make authz request to get details of custom roles
        $customRoles = [];

        if (!empty($customRoleIds))
        {
            $customRoles = (new \RZP\Models\AuthzAdmin\Service())->adminAPIListRole([
                Constants::ROLE_IDS  => $customRoleIds,
                Entity::TYPE         => V1RolePolicyType::CUSTOM,
            ]);
        }

        $rolesData = array_merge($standardRoles, $customRoles);

        $roleIdsOnAuthz = [];

        if (!empty($rolesData))
        {
            $roleIdsOnAuthz = array_column($rolesData, Entity::ID);
        }

        // 4. find role_ids which were not present on authz
        $roleIdsNotOnAuthz = array_filter($roleIds, function ($roleId) use ($roleIdsOnAuthz)
        {
            return !in_array($roleId, $roleIdsOnAuthz);
        });

        // 5. fetch details of roles which were not present on authz
        $rolesNotOnAuthz = $this->repo->roles->fetchByIds($roleIdsNotOnAuthz);

        if(!empty($rolesNotOnAuthz))
        {
            $rolesData = array_merge($rolesData, $rolesNotOnAuthz->toArray());
        }

        // 6. clean up the data
        $keysToRemove = [
            Entity::UPDATED_BY,
            Entity::CREATED_AT,
            Entity::UPDATED_AT,
            Entity::CHILD_IDS
        ];

        foreach ($rolesData as &$item) {
            foreach ($keysToRemove as $key) {
                unset($item[$key]);
            }
        }

        return $rolesData;
    }

    private function getRoleFromAuthz(string $roleId, string|null $merchantId)
    {
        try
        {
            $role = (new \RZP\Models\AuthzAdmin\Service())->adminAPIGetRole($roleId, $merchantId, false);

            if (empty($role) || empty($role[Entity::ID]))
            {
                return null;
            }

            unset($role[Entity::CHILD_IDS]);

            $role[Entity::ORG_ID] = Entity::ORG_ID_FOR_ROLES;

            $roleEntity = (new Entity)->fill($role);

            $roleEntity->setAttribute(Entity::ID, $role[Entity::ID]);

            return $roleEntity;
        }
        catch(Throwable $e)
        {
            $this->trace->error(TraceCode::GET_ROLE_FROM_AUTHZ_FAILED, [
                Entity::ROLE_ID     => $roleId,
                Entity::MERCHANT_ID => $merchantId,
                Constants::ERROR    => $e->getMessage()
            ]);

            $this->trace->count(Metrics::GET_ROLE_FROM_AUTHZ_FAILED);

            throw $e;
        }
    }

    private function getAuthzRolesForRoleFromAuthz(string $roleId, string|null $merchantId)
    {
        try
        {
            $role = (new \RZP\Models\AuthzAdmin\Service())->adminAPIGetRole($roleId, $merchantId, true);

            if (empty($role))
            {
                return [];
            }

            return $role[Entity::CHILD_NAMES] ?? [];
        }
        catch(Throwable $e)
        {
            $this->trace->error(TraceCode::GET_AUTHZ_ROLES_FOR_ROLE_FROM_AUTHZ_FAILED, [
                Entity::ROLE_ID     => $roleId,
                Entity::MERCHANT_ID => $merchantId,
                Constants::ERROR    => $e->getMessage()
            ]);

            $this->trace->count(Metrics::GET_AUTHZ_ROLES_FOR_ROLE_FROM_AUTHZ_FAILED);

            throw $e;
        }
    }

    private function locateRoleUsingExperiment(string $roleId) : array
    {
        $merchant = $this->merchant;

        $role = null;

        // 1. if merchant doesn't exist in auth, try to resolve merchant using role entity
        if (empty($merchant))
        {
            $role = $this->repo->roles->fetchRole($roleId);

            // 2. if role is not present on API, role has to be on authz if it exists
            if (empty($role))
            {
                return [
                    Constants::LOCATION => Constants::AUTHZ,
                    Entity::MERCHANT_ID => null,
                ];
            }
            else
            {
                $merchant = $role->merchant;
            }
        }

        // 3. if experiment is enabled for merchant, role should be resolved from authz
        $experimentEnabled = $merchant->checkCACMigrationExperimentEnabled();

        $this->trace->info(TraceCode::CAC_LOCATE_ROLE_USING_EXPERIMENT, [
            Entity::ROLE_ID                 => $roleId,
            Entity::MERCHANT_ID             => $merchant->getId(),
            Constants::EXPERIMENT_STATUS    => $experimentEnabled,
        ]);

        if ($experimentEnabled === true)
        {
            return [
                Constants::LOCATION => Constants::AUTHZ,
                Entity::MERCHANT_ID => $this->getMerchantIdForLocateRole($merchant->getId()),
            ];
        }

        return [
            Constants::LOCATION => Constants::API,
            Entity::MERCHANT_ID => $this->getMerchantIdForLocateRole($merchant->getId()),
            Constants::ROLE     => $role,
        ];
    }

    private function getMerchantIdForLocateRole(string $merchantId)
    {
        if ($merchantId === Entity::STANDARD_ROLE_MERCHANT_ID)
        {
            return strtolower($merchantId);
        }

        return $merchantId;
    }
}
