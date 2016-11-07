<?php

namespace RZP\Models\Admin\Admin\Token;

use RZP\Base;
use Carbon\Carbon;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    protected $entity = 'admin_token';

    public function createToken($admin)
    {
        $token = new Entity();

        $token->admin_id = $admin->id;
        $token->token = str_random(40);
        $token->expires_at = Carbon::now()->addHours(1)->timestamp;

        $token->save();

        return $token;
    }

    public function findValidToken($token)
    {
        return $this->newQuery()
                    ->where(Entity::TOKEN, '=', $token)
                    ->first();
    }
}
