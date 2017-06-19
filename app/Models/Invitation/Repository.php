<?php

namespace RZP\Models\Invitation;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'invitation';

    public function fetchByToken($token)
    {
    	return $this->newQuery()
                    ->where(Entity::TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }
}
