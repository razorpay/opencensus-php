<?php

namespace RZP\Models\Roles;


use App;
use Mail;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\User\BankingRole;
use RZP\Models\RoleAccessPolicyMap;
use RZP\Models\AccessControlHistoryLogs;
use RZP\Mail\Merchant\RazorpayX\RolePermissionChange;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $input, array $accessPolicyIds) :Entity
    {
        $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        if($this->checkIfRoleNameIsEligible($input['name']) === false)
        {
            throw new Exception\BadRequestValidationFailureException("Role Name Already Exists for Merchant" ,
                $input);
        }

        $input[Entity::TYPE] = Entity::CUSTOM;

        $user = $this->app['basicauth']->getUser();

        $userId = $user ? $user->getUserId() : null;

        $input[Entity::CREATED_BY] = $userId;

        $input[Entity::UPDATED_BY] = $userId;

        // assign merchant id
        $input[Entity::MERCHANT_ID] = $this->merchant->getId();

        $input[Entity::ORG_ID] = $this->merchant->getOrgId();

        $entity = (new Entity)->build($input);

        $this->repo->transactionOnLiveAndTest(function() use ($entity, $input, $accessPolicyIds)
        {
            $this->repo->saveOrFail($entity);

            $roleId = $entity->getId();

            $authzRoles = $this->repo->access_policy_authz_roles_map->getAllAuthzRolesForAccessPolicyIds($accessPolicyIds);

            $roleToAccessPolicyMapInput = [
                'role_id'            => $roleId,
                'authz_roles'        => $authzRoles,
                'access_policy_ids'  => $accessPolicyIds
            ];

            $roleToAccessPolicy = (new RoleAccessPolicyMap\Service())->create($roleToAccessPolicyMapInput);

            $this->addEntryInHistoryLogs([],
                array_merge($entity->toArrayPublic(), $roleToAccessPolicy),
                "Role has been created");
        });

        $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_CREATE_RESPONSE,
            ['role_id' => $entity->getId()]);

        return $entity;

    }

    public function edit(string $id, array $input, array $accessPolicyIds) :Entity
    {
        if(empty($id) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Role Id" ,
                $input);
        }

        $role = $this->repo->roles->findOrFailByPublicIdWithParams($id, []);

        if(empty($role) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Invalid Role Id" ,
                $input);
        }

        if($role->getType() === Entity::STANDARD)
        {
            throw new Exception\BadRequestValidationFailureException("Can't Edit Standard Roles" ,
                $input);
        }
        $user = $this->app['basicauth']->getUser();

        $userId = $user ? $user->getUserId() : null;

        $input[Entity::UPDATED_BY] = $userId;

        $previousRoleMapEntity = $this->repo->role_access_policy_map->findByRoleId($id)->toArrayPublic();

        $previousRoleEntity = array_merge($role->toArrayPublic(), $previousRoleMapEntity);

        // if old and new name is not same then check role name eligibility
        if($role->getName() != $input['name'])
        {
            if($this->checkIfRoleNameIsEligible($input['name']) === false)
            {
                throw new Exception\BadRequestValidationFailureException("Role Name Already Exists for Merchant" ,
                    $input);
            }
        }

        $role->edit($input);

        $this->repo->transactionOnLiveAndTest(function() use ($role, $input, $accessPolicyIds, $previousRoleEntity)
        {
            $this->repo->saveOrFail($role);

            $roleId = $role->getId();

            $authzRoles = $this->repo->access_policy_authz_roles_map->getAllAuthzRolesForAccessPolicyIds($accessPolicyIds);

            $roleToAccessPolicyMapInput = [
                'role_id'           => $roleId,
                'authz_roles'       => $authzRoles,
                'access_policy_ids' => $accessPolicyIds
            ];

            $roleAccessPolicyMap = (new RoleAccessPolicyMap\Service())->edit($roleToAccessPolicyMapInput);

            $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_CHANGED_POLICY,
                ['roleAccessPolicyMap' => $roleAccessPolicyMap,
                    'role_id'=> $roleId
                ]);

            $this->sendEmail($roleId);
            $this->addEntryInHistoryLogs(
                $previousRoleEntity,
                array_merge($role->toArrayPublic(), $roleAccessPolicyMap),
                'Role has been edited'
            );
        });

        return $role;
    }

    protected function sendEmail(string $roleId)
    {
        $merchantId = $this->merchant->getId();

        /* loggedIn merchant business name */
        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);
        $senderName = $merchantDetail->getBusinessName();

        /* get loggedIn merchant user role */
        $user = $this->app['basicauth']->getUser();
        $merchantUserRole = $this->repo->merchant_user->getMerchantUserRoles($user->getId(),$merchantId);
        $merchantRoleId = array_pluck($merchantUserRole->toArray(), User\Entity::ROLE)[0];
        $merchantRole = $this->repo->roles->fetchRoleName($merchantRoleId);
        /* get userEmail & userName for merchant have roleId that has been edited */
        $merchantUsers = $this->repo->merchant_user
            ->findByRolesAndMerchantId([$roleId], $merchantId);
        $userIds = array_pluck($merchantUsers->toArray(), 'user_id');
        $users = $this->repo->user->findManyByPublicIds($userIds);

        foreach ($users as $userEntity)
        {
            $rolePermissionMailer = new RolePermissionChange($senderName, $userEntity, $merchantRole);

            $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_EDIT_MAIL,
                ['user_id' => $userEntity->getId(),
                    'senderName' => $senderName,
                    'role' => $merchantRole
                ]);

            Mail::queue($rolePermissionMailer);
        }
    }

    public function listRolesForMerchant($input)
    {
        $this->setInputParamForListRoles($input);

        $roles = $this->repo->roles->listRoles($input);

        $roles = $roles->whereNotIn(Entity::ID, Entity::$rolesHiddenFromDashboard);

        $this->trace->info(
            TraceCode::RECOVERABLE_EXCEPTION,
            ['roles' => $roles->toArray()]);

        if (empty($roles) === true)
        {
            throw new Exception\BadRequestValidationFailureException("Role not found" ,
                [$input]);
        }

        $roleIds = $roles
            ->pluck(Entity::ID)
            ->toArray();

        $rolesGroupedByType = $roles
            ->groupBy(Entity::TYPE)
            ->toArray();

        $userCount = $this->repo->merchant_user->getBankingUserCountByMerchantIdAndRoleIdsAsQuery($this->merchant->getId(), $roleIds)->groupBy('role')->toArray();

        foreach ($rolesGroupedByType as & $roles)
        {
            foreach ($roles as & $role)
            {
                //disable copy of admin role
                $role[Entity::COPY_DISABLE] = false;

                if(in_array($role[Entity::ID], Entity::$disableCopyForRoles) === true)
                {
                    $role[Entity::COPY_DISABLE] = true;
                }

                //add member count in response
                if (empty($userCount[$role[Entity::ID]]) === false)
                {
                    $role[Entity::MEMBERS] = $userCount[$role[Entity::ID]][0]['count'];
                }
                else
                {
                    $role[Entity::MEMBERS] = 0;
                }
            }
        }

        return $rolesGroupedByType;
    }

    public function listRoles($input)
    {
        return $this->repo->roles->listRoles($input)->toArrayPublic();
    }

    public function setInputParamForListRoles(& $input)
    {
        if (isset($input[Entity::TYPE]) === false)
        {
            $input[Entity::TYPE] = [
                Entity::CUSTOM,
                Entity::STANDARD
            ];
        }
        else
        {
            $input[Entity::TYPE] = [$input[Entity::TYPE]];
        }

        $input[Entity::MERCHANT_ID] = $this->merchant->getId();
    }

    // TODO : remove this function after creating standard roles in prod
    public function createStandardRole(array $input, array $accessPolicyIds)
    {
        $input[Entity::CREATED_AT] = time();

        $input[Entity::UPDATED_AT] = time();

        $input[Entity::ORG_ID] = Entity::ORG_ID_FOR_ROLES;

        $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_CREATE_REQUEST,
            [
                'input' => $input
            ]);

        $this->repo->transactionOnLiveAndTest(function () use ($input, $accessPolicyIds)
        {
            $this->repo->roles->insertRecord($input);

            $authzRoles = $this->repo->access_policy_authz_roles_map->getAllAuthzRolesForAccessPolicyIds($accessPolicyIds);

            $roleToAccessPolicyMapInput = [
                'role_id'           => $input['id'],
                'authz_roles'       => $authzRoles,
                'access_policy_ids' => $accessPolicyIds
            ];

            (new RoleAccessPolicyMap\Service())->create($roleToAccessPolicyMapInput);

        });

        $this->trace->info(TraceCode::ACCESS_CONTROL_ROLES_CREATE_RESPONSE,
            ['role_id' => $input['id']]);
    }

    private function addEntryInHistoryLogs(array $previousRoleEntity, array $newRoleEntity, string $message)
    {
        if($this->checkIfOldAndNewEntitiesAreSame($previousRoleEntity, $newRoleEntity) === true)
        {
            return;
        }
        $historyLogInput = [];

        $historyLogInput[AccessControlHistoryLogs\Entity::MESSAGE] = $message;

        $historyLogInput[AccessControlHistoryLogs\Entity::OWNER_ID] = $this->merchant->getId();

        $historyLogInput[AccessControlHistoryLogs\Entity::OWNER_TYPE] = 'merchant';

        if(!empty($previousRoleEntity))
        {
            unset($previousRoleEntity[Entity::ID]);
            $historyLogInput[AccessControlHistoryLogs\Entity::ENTITY_ID] =
                $previousRoleEntity[RoleAccessPolicyMap\Entity::ROLE_ID];
        }

        $historyLogInput[AccessControlHistoryLogs\Entity::PREVIOUS_VALUE] = $previousRoleEntity;

        if(!empty($newRoleEntity))
        {
            unset($newRoleEntity[Entity::ID]);
            $historyLogInput[AccessControlHistoryLogs\Entity::ENTITY_ID] =
                $newRoleEntity[RoleAccessPolicyMap\Entity::ROLE_ID];
        }

        $historyLogInput[AccessControlHistoryLogs\Entity::NEW_VALUE] = $newRoleEntity;

        $historyLogInput[AccessControlHistoryLogs\Entity::ENTITY_TYPE] = \RZP\Models\AccessControlHistoryLogs\Entity::ENTITY_TYPE_ROLE;

        (new \RZP\Models\AccessControlHistoryLogs\Service())->create($historyLogInput);
    }

    private function checkIfOldAndNewEntitiesAreSame(array $previousRoleEntity, array $newRoleEntity) :bool
    {
        if(empty($previousRoleEntity) === true and empty($newRoleEntity) === false)
        {
            return false;
        }

        if(empty($previousRoleEntity) === false and empty($newRoleEntity) === true)
        {
            return false;
        }

        if($previousRoleEntity[Entity::NAME] != $newRoleEntity[Entity::NAME])
        {
            return false;
        }

        if($previousRoleEntity[Entity::DESCRIPTION] != $newRoleEntity[Entity::DESCRIPTION])
        {
            return false;
        }

        if(Validator::validateArrayEqual($previousRoleEntity[Entity::ACCESS_POLICY_IDS],
            $newRoleEntity[Entity::ACCESS_POLICY_IDS]) === false)
        {
            return false;
        }

        if(Validator::validateArrayEqual($previousRoleEntity[RoleAccessPolicyMap\Entity::AUTHZ_ROLES],
            $newRoleEntity[RoleAccessPolicyMap\Entity::AUTHZ_ROLES]) === false)
        {
            return false;
        }

        return true;
    }

    public function delete(Entity $role) :array
    {
        $this->repo->transactionOnLiveAndTest(function() use ($role)
        {
            $roleAccessMap = $this->repo->role_access_policy_map->findByRoleId($role->getId());

            $roleData = array_merge($role->toArrayPublic(), $roleAccessMap->toArrayPublic());

            $this->repo->role_access_policy_map->delete($roleAccessMap);

            $this->repo->delete($role);

            $this->addEntryInHistoryLogs($roleData, [], 'Role has been deleted');
        });

        return $role->toArrayPublic();
    }

    private function checkIfRoleNameIsEligible(string $name) :bool
    {
        $merchantId = $this->merchant->getId();

        $role = $this->repo->roles->findNameByStandRolesOrMerchantId($name, $merchantId);

        if(empty($role) === true)
        {
            return true;
        }
        return false;
    }

    //currently roles list will have existing finance roles(fl1, fl2, fl3) and new finance role
    // So, if merchant has users linked with existing finance role then we show (fl1, fl2, fl3) otherwise just finance role
    public function filterFinanceRoleForMerchant(string $merchantId, array $roles):  array
    {
        $excludedRoles = [
            BankingRole::FINANCE_L1,
            BankingRole::FINANCE_L2,
            BankingRole::FINANCE_L3,
        ];

        $userCount = $this->repo->merchant_user->getBankingUserCountByMerchantIdAndRoleIds($merchantId, $excludedRoles);

        if ($userCount > 0)
        {
            $excludedRoles = [
                 BankingRole::FINANCE
            ];
        }

        $filteredRoles = [];

        foreach($roles as $index => $role)
        {
            if( in_array($role['id'], $excludedRoles) === false)
            {
                $filteredRoles[] = $role;
            }
        }

        return $filteredRoles;
    }

    public function checkIfRoleIsStandardRole($roleId) :bool
    {
        $role = $this->repo->roles->fetchRole($roleId);

        if(empty($role) === true)
        {
            return false;
        }

        return $role->getType() === Entity::STANDARD;
    }
}
