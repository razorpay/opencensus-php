<?php

namespace Models\Customer\Methods;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer\Methods;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'CustomerMethods';

    public function getByParams($params)
    {
        $repo = $this->repo;

        $query = (new $repo)->newQuery();

        foreach ($params as $key => $value)
        {
            $query = $query->where($key, '=', $value);
        }

        return $query->get();
    }

    public function getById($id)
    {
        $repo = $this->repo;

        return $repo::findOrFailPublic($id);
    }

    public function getByCustomerId($uid)
    {
        $repo = $this->repo;

        return $repo::where(Methods\Entity::CUSTOMER_ID, '=', $uid)
                    //->where(Methods\Entity::METHOD, '=', 'card')
                    ->get();
    }

    public function getByIdAndCustomerId($uid, $mid)
    {
        $repo = $this->repo;

        return $repo::where(Methods\Entity::CUSTOMER_ID, '=', $uid)
                    ->findOrFailPublic($mid);
    }
}
