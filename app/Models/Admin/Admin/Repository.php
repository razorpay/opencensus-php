<?php

namespace RZP\Models\Admin\Admin;

use RZP\Base;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    public function getByUsername($username)
    {
        return $this->newQuery()
                    ->where('username', '=', $username)
                    ->first();
    }
}
