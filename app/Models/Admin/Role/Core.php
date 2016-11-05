<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($orgId, array $input)
    {
        Org::verifyIdAndStripSign($orgId);

        $input['org_id'] = $orgId;

        $role = (new Entity)->build($input);

        $this->repo->saveOrFail($role);

        return $role;
    }
}
