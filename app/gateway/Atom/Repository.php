<?php

namespace Gateway\Atom;

use EE\Exception;
use Gateway\Atom;
use Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Atom';

    public function findByToken($token)
    {
        $repo = $this->repo;

        return $repo::where('token', '=', $token)
                    ->first();
    }
}