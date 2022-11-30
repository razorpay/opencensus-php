<?php

namespace RZP\Models\Roles;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Admin\Role as AdminRole;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\User\BankingRole;
use RZP\Models\RoleAccessPolicyMap;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Merchant\RazorxTreatment;

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

        $roles = $this->repo->roles->findOrFailByPublicIdWithParams($id, $input)->toArrayPublicWithExpand();

        $roles[RoleAccessPolicyMap\Entity::ACCESS_POLICY_IDS] =
            $roles[Entity::ACCESS_POLICY][RoleAccessPolicyMap\Entity::ACCESS_POLICY_IDS] ?: null;

        unset($roles[Entity::ACCESS_POLICY]);

        // get user count for this role for merchant
        $merchantId = $this->merchant->getId();

        $roles[Entity::MEMBERS] = $this->repo->merchant_user->getBankingUserCountByMerchantIdAndRoleIds($merchantId, [$roles[Entity::ID]]);

        return $roles;
    }

    public function create(array $input) :array
    {
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
}
