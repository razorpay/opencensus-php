<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Admin\Org;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Service extends Base\Service
{
    public function login($input)
    {
        // Get the admin record
        $admin = $this->repo->admin->getByUsername($input['username']);

        // Valid password ?
        if (\Hash::check($input['password'], $admin->password))
        {
            // Create a token for the user
            $token = $this->createAuthToken($admin);

            $admin = $admin->toArrayPublic();
            $admin['token'] = $token->token;

            return $admin;
        }

        return null;
    }

    private function createAuthToken($admin)
    {
        $token = $this->repo->admin_token->createToken($admin);

        return $token;
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
}
