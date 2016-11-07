<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($orgId, array $input)
    {
        Org::verifyIdAndStripSign($orgId);

        $role = (new Entity)->build($input);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $role->org()->associate($org);

        $this->repo->saveOrFail($role);

        return $role;
    }
}
