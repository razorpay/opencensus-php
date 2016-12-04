<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;

class Core extends Base\Core
{
    public function create(Org\Entity $org, array $input)
    {
        $admin = (new Entity)->generateId();

        $admin->org()->associate($org);

        $admin->build($input);

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $org->getId(), $admin->getId());

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

    public function edit(Entity $admin, array $input)
    {
        $admin->edit($input);

        $this->repo->saveOrFail($admin);

        $this->associateRelevantEntitiesToAdmin($admin, $input);

        $admin = $this->repo->admin->retrieveByOrgIdAndIdOrFail(
            $admin['org_id'], $admin->getId());

        return $admin;
    }

    public function associateRelevantEntitiesToAdmin(Entity $admin, array $input)
    {
        if (isset($input['roles']) === true)
        {
            Role\Entity::verifyIdAndStripSignMultiple($input['roles']);

            $this->repo->sync($admin, 'roles',  $input['roles']);
        }
        else
        {
            $this->repo->sync($admin, 'roles',  []);
        }

        if (isset($input['groups']) === true)
        {
            Group\Entity::verifyIdAndStripSignMultiple($input['groups']);

            $this->repo->sync($admin, 'groups', $input['groups']);
        }
        else
        {
            $this->repo->sync($admin, 'groups', []);
        }
    }
}

