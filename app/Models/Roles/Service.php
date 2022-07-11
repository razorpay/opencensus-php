<?php

namespace RZP\Models\Roles;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\RoleAccessPolicyMap;

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

        return $rolesGrouppedByType;
    }

    public function fetch(string $id, array $input): array
    {
        $input['expand'] = [Entity::ACCESS_POLICY];

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
