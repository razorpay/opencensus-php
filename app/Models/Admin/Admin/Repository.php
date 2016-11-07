<?php

namespace RZP\Models\Admin\Admin;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    // TODO Define the proxyfetch and admin fetch params

    public function getByUsername($username)
    {
        return $this->newQuery()
                    ->where(Entity::USERNAME, '=', $username)
                    ->first();
    }

    public function retrieveByIdAndAdminIdOrFail($orgId, $adminId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::ID, '=', $adminId)
                    ->firstOrFail();
    }
}
