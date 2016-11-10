<?php

namespace RZP\Models\Admin\Admin;

use Hash;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Org\AuthPolicy;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function login($input)
    {
        // TODO: add validation for username and password (required fields)

        // Get the admin record
        $admin = $this->repo->admin->findOrFailByUsername($input['username']);

        $validate = (new AuthPolicy\Service)
                        ->validateLogin($admin);

        if ($validate !== null)
        {
            return $validate;
        }

        // Valid password ?
        if (Hash::check($input['password'], $admin->getPassword()))
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
        else
        {
            $admin->incrementFailedAttempts();
            $this->repo->saveOrFail($admin);
        }

        return null;
    }

    public function createAdmin(string $orgId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);

        $admin = $this->core->create($orgId, $input);

        return $admin->toArray();
    }

    public function getAdmin(string $orgId, string $adminId)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        return $admin->toArrayPublic();
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

    public function addRoleToAdmin(
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

        $this->repo->admin->addRoleToAdmin($admin, $role);
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
}
