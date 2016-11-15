<?php

namespace RZP\Models\Admin\Admin;

use Hash;
use RZP\Error;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org\AuthPolicy;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->validator = new Validator;

        $this->authPolicy = new AuthPolicy\Service;
    }

    public function login($input)
    {
        $this->validator->validateCredentials($input);

        // Get the admin record
        $admin = $this->repo->admin->findOrFailByEmail($input['username']);

        if ($admin === null)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $this->authPolicy->validateLogin($admin, $input['password']);

        // Valid password ?
        if (Hash::check($input['password'], $admin->getPassword()))
        {
            $data = $this->generateLoginToken($admin);

            $validate = $this->authPolicy->validateLogin($admin, $input['password'], 'after');

            if ($validate !== null)
            {
                return $validate;
            }

            return $data;
        }
        else
        {
            $admin->incrementFailedAttempts();
            $this->repo->saveOrFail($admin);
        }

        return null;
    }

    public function loginWithOAuth($input)
    {
        // TODO: error validation

        // Get the admin record
        $admin = $this->repo->admin->findOrFailByEmail($input['email']);

        // Valid token ?
        if (($admin->oauth_access_token === $input['oauth_access_token']) and
            ($admin->oauth_provider_id === $input['oauth_provider_id']))
        {
            $data = $this->generateLoginToken($admin);

            return $data;
        }
        else
        {
            $admin->incrementFailedAttempts();
            $this->repo->saveOrFail($admin);
        }

        return null;
    }

    private function generateLoginToken($admin)
    {
        $admin->resetFailedAttempts();
        $this->repo->saveOrFail($admin);

        $tokenAttributes = [
            'token'      => str_random(40),
            'expires_at' => Carbon::now()->addHours(1)->timestamp
        ];

        // Create a token for the user
        $token = $this->core->createAuthToken($admin, $tokenAttributes);

        $admin = $admin->toArrayPublic();

        $admin['token'] = $token->getToken();

        return $admin;
    }

    public function createAdmin(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        if (isset($input['roles']) === true)
        {
            $roleIds = [];

            foreach ($input['roles'] as $roleId)
            {
                $roleIds[] = Role\Entity::verifyIdAndStripSign($roleId);
            }

            $input['roles'] = $roleIds;
        }

        if (isset($input['groups']) === true)
        {
            $groupIds = [];

            foreach ($input['groups'] as $id)
            {
                $groupIds[] = Group\Entity::verifyIdAndStripSign($id);
            }

            $input['groups'] = $groupIds;
        }

        if (isset($input['merchants']) === true)
        {
            $merchantIds = [];

            foreach ($input['merchants'] as $id)
            {
                $merchantIds[] = Merchant\Entity::verifyIdAndStripSign($id);
            }

            $input['merchants'] = $merchantIds;
        }

        $admin = $this->core->create($orgId, $input);

        return $admin->toArrayPublic();
    }

    public function getAdmin(string $orgId, string $adminId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        return $admin->toArrayPublic();
    }

    public function getAdminByAppAuth(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $token = $input['token'];

        $adminToken = $this->repo->admin_token->retrieveByToken($token);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail($orgId, $adminToken->getAdminId());

        $roles = $admin->roles;
        $permissions = [];
        $roleNames = [];
        $groupRules = [];

        foreach ($admin->groups as $group)
        {
            $groupRules[] = [
                'name' => $group['name'],
                'description' => $group['description'],
            ];
        }

        foreach ($roles as $role)
        {
            $roleNames[] = $role['name'];
            $rolePermissions = $role->permissions;

            foreach ($rolePermissions as $rolePermission)
            {
                $rolePermission = $rolePermission->toArrayPublic();
                $permissions[] = $rolePermission['name'];
            }
        }

        $admin = $admin->toArrayPublic();

        $admin['permissions'] = $permissions;

        $admin['roles'] = $roleNames;

        $admin['groups'] = $groupRules;

        return $admin;
    }

    public function getAdminByAttr($orgId, $attr, $attrVal)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $admin = $this->repo->admin->findOrFailByAttr($orgId, $attr, $attrVal);

        return $admin->toArrayPublic();
    }

    public function deleteAdmin(string $orgId, string $adminId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);

        $data = $this->core->delete($orgId, $adminId);

        return $data;
    }

    public function updateRolesForAdmin(
        string $orgId,
        string $adminId,
        array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);

        $roleIds = [];

        foreach ($input['roles']  as $roleId)
        {
            $roleIds[] = Role\Entity::verifyIdAndStripSign($roleId);
        }

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        $admin->roles()->sync($roleIds);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        return $admin->toArrayPublic();
    }

    public function addMerchantToAdmin(
        string $orgId,
        string $adminId,
        string $merchantId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);
        $merchantId = Merchant::verifyIdAndStripSign($merchantId);

        $merchant = $this->repo->merchant->findOrFail($merchantId);
        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail($orgId, $adminId);

        $this->repo->admin->addMerchantToAdmin($admin, $merchant);
    }

    public function revokeRoleFromAdmin(
        string $orgId,
        string $adminId,
        string $roleId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);
        $roleId = Merchant\Entity::verifyIdAndStripSign($roleId);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        $role = $this->repo->role->findOrFail($roleId);

        $this->repo->admin->revokeRoleOrFail($admin, $role);
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $admins = $this->repo->admin->fetchAdminsForOrg($orgId, $input);

        return $admins->toArrayPublic();
    }

    public function  editAdmin(string $orgId, string $adminId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);

        if (isset($input['roles']) === true)
        {
            $roleIds = [];

            foreach ($input['roles'] as $roleId)
            {
                $roleIds[] = Role\Entity::verifyIdAndStripSign($roleId);
            }

            $input['roles'] = $roleIds;
        }

        if (isset($input['groups']) === true)
        {
            $groupIds = [];

            foreach ($input['groups'] as $id)
            {
                $groupIds[] = Group\Entity::verifyIdAndStripSign($id);
            }

            $input['groups'] = $groupIds;
        }

        $admin = $this->core->edit($orgId, $adminId, $input);

        return $admin->toArrayPublic();
    }
}
