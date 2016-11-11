<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Org\AuthPolicy;

class Core extends Base\Core
{
    public function create(string $orgId, array $input)
    {
        $org = $this->repo->org->findOrFail($orgId);

        $admin = (new Entity)->build($input);

        $admin->org()->associate($org);

        if (isset($input['password']) === true)
        {
            (new AuthPolicy\Service)
                ->validate($admin, $input['password']);

            $admin->setOldPasswords();
        }

        $this->repo->saveOrFail($admin);

        if (isset($input['roles']) === true)
        {
            foreach ($input['roles'] as $roleId)
            {
                $roleId = Role\Entity::verifyIdAndStripSign($roleId);

                $role = $this->repo->role->retrieveByOrgIdAndIdOrFail($orgId, $roleId);

                $admin->roles()->attach($role);
            }
        }

        if (isset($input['merchants']) === true)
        {
            foreach ($input['merchants'] as $id)
            {
                $id = Merchant\Entity::verifyIdAndStripSign($id);

                $merchant = $this->repo->merchant->findOrFail($id);

                $admin->merchants()->attach($merchant);
            }
        }

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail($orgId, $admin->getId());

        return $admin;
    }

    public function createAuthToken(Entity $admin, array $input)
    {
        $token = new Token\Entity();

        $token->build($input);
        $token->admin()->associate($admin);

        $token->saveOrFail();

        return $token;
    }

    public function delete(string $orgId, string $adminId)
    {
        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        $this->repo->deleteOrFail($admin);

        return $admin;
    }
}

