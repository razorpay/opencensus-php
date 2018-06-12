<?php

namespace RZP\Models\Invitation;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'invitation';

    public function fetchByToken($token)
    {
    	return $this->newQuery()
                    ->where(Entity::TOKEN, $token)
                    ->firstOrFailPublic();
    }

    public function findByIdAndEmail(string $id, string $email)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->where(Entity::EMAIL, $email)
                    ->firstOrFailPublic();
    }
}
