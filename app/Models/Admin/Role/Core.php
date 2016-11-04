<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $role = (new Entity)->build($input);

        $this->repo->saveOrFail($role);

        return $role;
    }
}
