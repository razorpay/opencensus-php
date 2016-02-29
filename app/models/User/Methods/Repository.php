<?php

namespace Models\User\Methods;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\User\Methods;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'UserMethods';

    public function getById($id)
    {
        $repo = $this->repo;

        return $repo::findOrFailPublic($id);
    }

    public function getByUserId($uid)
    {
        $repo = $this->repo;

        return $repo::where(Methods\Entity::USER_ID, '=', $uid)
                    ->get();
    }

    public function getByIdAndUserId($uid, $mid)
    {
        $repo = $this->repo;

        return $repo::where(Methods\Entity::USER_ID, '=', $uid)
                    ->findOrFailPublic($mid);
    }
}
