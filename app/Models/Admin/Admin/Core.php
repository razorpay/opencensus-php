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
            $admin->roles()->sync($input['roles']);
        }

        if (isset($input['merchants']) === true)
        {
            $admin->merchants()->sync($input['merchants']);
        }

        if (isset($input['groups']) === true)
        {
            $admin->groups()->sync($input['groups']);
        }

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $admin->getId());

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
        // Delete the admin tokens first
        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $adminId);

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

        return ['success' => true];
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
            $admin->roles()->sync($input['roles']);
        }

        if (isset($input['merchants']) === true)
        {
            $admin->merchants()->sync($input['merchants']);
        }

        if (isset($input['groups']) === true)
        {
            $admin->groups()->sync($input['groups']);
        }

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $orgId, $admin->getId());

        return $admin;
    }
}

