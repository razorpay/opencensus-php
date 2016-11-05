<?php

namespace RZP\Models\Admin\Admin;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'admin';

    public function getByUsername($username)
    {
        return $this->newQuery()
                    ->where('username', '=', $username)
                    ->first();
    }
}
