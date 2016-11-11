<?php

namespace RZP\Models\Admin\Admin\Token;

use RZP\Base;
use Carbon\Carbon;

class Repository extends Base\Repository
{
    protected $entity = 'admin_token';

    public function findValidToken($token)
    {
        return $this->newQuery()
                    ->where(Entity::TOKEN, '=', $token)
                    ->first();
    }

    public function retrieveByToken(string $token)
    {
        return $this->newQuery()
                    ->where(Entity::TOKEN, '=', $token)
                    ->firstOrFail();
    }
}
