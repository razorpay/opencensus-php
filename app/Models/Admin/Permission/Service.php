<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createPermission($orgId, array $input)
    {
        $permission = (new Core)->create($orgId, $input);

        return $permission->toArrayPublic();
    }

}
