<?php

namespace Models\Customer\Token;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer\Token;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Token';

    protected $appFetchParamRules = array(
        Entity::METHOD          => 'sometimes|alpha',
        Entity::CUSTOMER_ID     => 'sometimes|alpha_num',
    );

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
