<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($orgId, array $input)
    {
        $permission = (new Entity)->build($input);

        $this->repo->saveOrFail($permission);

        return $permission;
    }
}
