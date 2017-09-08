<?php

namespace RZP\Models\Admin\Admin\Token;

use RZP\Models\Admin\Base;
use Carbon\Carbon;

class Repository extends Base\Repository
{
    protected $entity = 'admin_token';

    public function findOrFailToken($token)
    {
        return $this->newQuery()
                    ->with('admin')
                    ->where(Entity::TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }
}
