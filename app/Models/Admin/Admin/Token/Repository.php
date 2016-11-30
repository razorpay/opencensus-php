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

    public function retrieveByToken(string $token)
    {
        return $this->newQuery()
                    ->where(Entity::TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }

    public function fetchTokensByAdminId(string $adminId)
    {
        $tokenExists = $this->newQuery()
                            ->where(Entity::ADMIN_ID, '=', $adminId)
                            ->exists();

        if ($tokenExists === true)
        {
            return $this->newQuery()
                        ->where(Entity::ADMIN_ID, '=', $adminId)
                        ->get();
        }
        else
        {
            return array();
        }
    }
}
