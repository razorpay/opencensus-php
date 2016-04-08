<?php

namespace Models\Customer\Token;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer\Token;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Tokens';

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

    public function getByCustomerId($id)
    {
        $repo = $this->repo;

        return $repo::where(Token\Entity::CUSTOMER_ID, '=', $id)
                    ->get();
    }

    public function getByTokenAndCustomerId($id, $token)
    {
        $repo = $this->repo;

        return $repo::where(Token\Entity::CUSTOMER_ID, '=', $id)
                    ->where(Token\Entity::TOKEN, '=', $token)
                    ->first();
    }
}
