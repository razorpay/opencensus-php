<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;
use RZP\Models\Org\AuthPolicy;

class Core extends Base\Core
{
    public function create(string $orgId, array $input)
    {
        $org = $this->repo->org->findOrFail($orgId);

        (new AuthPolicy\Service)
            ->validate($orgId, $input['password']);

        $admin = (new Entity)->build($input);

        $admin->org()->associate($org);

        $this->repo->saveOrFail($admin);

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

