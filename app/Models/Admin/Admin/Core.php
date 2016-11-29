<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Org\AuthPolicy;
use RZP\Models\Admin\Role;

class Core extends Base\Core
{
    public function create(array $input, Org\Entity $org)
    {
        $admin = (new Entity);

        $admin->org()->associate($org);

        $admin->build($input);

        if (isset($input['password']) === true)
        {
            (new AuthPolicy\Service)->validate($admin, $input['password']);

            $admin->setOldPasswords();
        }

        $this->repo->saveOrFail($admin);

        if (isset($input['roles']) === true)
        {
            Role\Entity::verifyIdAndStripSignMultiple($input['roles']);
            $admin->roles()->sync($input['roles']);
        }

        if (isset($input['merchants']) === true)
        {
            Merchant\Entity::verifyIdAndStripSignMultiple($input['merchants']);
            $admin->merchants()->sync($input['merchants']);
        }

        if (isset($input['groups']) === true)
        {
            Group\Entity::verifyIdAndStripSignMultiple($input['groups']);
            $admin->groups()->sync($input['groups']);
        }

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

    public function delete(Entity $admin)
    {
        // Delete the admin tokens first
        $adminTokens = $this->repo->admin_token->fetchTokensByAdminId($admin->getId());

        if (empty($adminTokens) === true)
        {
            $this->repo->deleteOrFail($admin);
        }
        else
        {
            foreach ($adminTokens as $adminToken)
            {
                $this->repo->deleteOrFail($adminToken);
            }

            $this->repo->deleteOrFail($admin);
        }

        // @todo: To maintain bc. Remove first two lines later.
        $ret = $admin->toArrayDeleted();
        $ret = array_merge($ret, ['success' => true]);
        return $ret;
    }

    public function edit(string $orgId, string $adminId, array $input)
    {
        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

        $admin->edit($input);

        if (isset($input['password']) === true)
        {
            (new AuthPolicy\Service)
                ->validate($admin, $input['password']);

            $admin->setOldPasswords();

            if ($admin->getLastLoginAt() === null)
            {
                $admin->updateLastLoginAt();
            }
        }

        $this->repo->saveOrFail($admin);

        if (isset($input['roles']) === true)
        {
            Role\Entity::verifyIdAndStripSignMultiple($input['roles']);
            $admin->roles()->sync($input['roles']);
        }

        if (isset($input['merchants']) === true)
        {
            Merchant\Entity::verifyIdAndStripSignMultiple($input['merchants']);
            $admin->merchants()->sync($input['merchants']);
        }

        if (isset($input['groups']) === true)
        {
            Group\Entity::verifyIdAndStripSignMultiple($input['groups']);
            $admin->groups()->sync($input['groups']);
        }

        // $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
        //     $orgId, $admin->getId());

        return $admin;
    }
}

