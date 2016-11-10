<?php

namespace RZP\Models\Admin\Admin;

use Hash;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\AuthPolicy;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function login($input)
    {
        (new Validator)->validateCredentials($input);

        // Get the admin record
        $admin = $this->repo->admin->findOrFailByEmail($input['username']);

        $validate = (new AuthPolicy\Service)
                        ->validateLogin($admin, $input['password']);

        if ($validate !== null)
        {
            return $validate;
        }

        // Valid password ?
        if (Hash::check($input['password'], $admin->getPassword()))
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

    public function  editAdmin(string $orgId, string $adminId, array $input)
    {
        $orgId = Org\Entity::verifyIdAndStripSign($orgId);
        $adminId = Entity::verifyIdAndStripSign($adminId);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        $admin->edit($input);

        if (isset($input['password']) === true)
        {
            (new AuthPolicy\Service)
                ->validate($admin, $input['password']);

            $admin->setOldPasswords();

            $admin->updateLastLoginAt();
        }

        $this->repo->saveOrFail($admin);

        return $admin->toArrayPublic();
    }
}
