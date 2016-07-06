<?php

namespace Models\Customer\Token;

use RZP\Exception;
use RZP\Error\ErrorCode;
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

    public function getByTokenAndCustomerId($token, $id)
    {
        $repo = $this->repo;

        return $repo::where(Token\Entity::CUSTOMER_ID, '=', $id)
                    ->where(Token\Entity::TOKEN, '=', $token)
                    ->first();
    }

    public function getByWalletTerminalAndCustomerId($wallet, $terminal, $customer)
    {
        $repo = $this->repo;

        return $repo::where(Token\Entity::WALLET, '=', $wallet)
                    ->where(Token\Entity::TERMINAL_ID, '=', $terminal)
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer)
                    ->first();
    }

    public function getByMethodAndCustomerId($method, $customerId)
    {
        return $this->newQuery()
                    ->where(Entity::METHOD, '=', $method)
                    ->where(Entity::CUSTOMER_ID, '=', $customerId)
                    ->get();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}
