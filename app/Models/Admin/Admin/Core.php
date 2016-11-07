<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(string $orgId, array $input)
    {
        $org = $this->repo->org->findOrFailPublic($orgId);

        $admin = (new Entity)->build($input);

        $admin->org()->associate($org);

        $this->repo->saveOrFail($admin);

        return $admin;
    }
}

