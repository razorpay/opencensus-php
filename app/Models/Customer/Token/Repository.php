<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Customer\Token;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Token';

    protected $appFetchParamRules = array(
        Entity::METHOD          => 'sometimes|alpha',
        Entity::CUSTOMER_ID     => 'sometimes|alpha_num',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TERMINAL_ID     => 'sometimes|alpha_num',
        Entity::TOKEN           => 'sometimes|alpha_num',
        Entity::CARD_ID         => 'sometimes|alpha_num',
        Entity::BANK            => 'sometimes|alpha',
        Entity::WALLET          => 'sometimes|alpha',
    );

    public function getByCustomerId($id)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $id)
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }

    public function getByTokenAndCustomerId($token, $id, $recurring = false)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $id)
                    ->where(Token\Entity::TOKEN, '=', $token)
                    ->where(Token\Entity::RECURRING, '=', $recurring)
                    ->first();
    }

    public function getRecurringByTokenAndCustomerId($token, $id)
    {
        return $this->getByTokenAndCustomerId($token, $id, true);
    }

    public function getByWalletTerminalAndCustomerId($wallet, $terminal, $customer)
    {
        return $this->newQuery()
                    ->where(Token\Entity::WALLET, '=', $wallet)
                    ->where(Token\Entity::TERMINAL_ID, '=', $terminal)
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer)
                    ->first();
    }

    public function getByMethodAndCustomerId($method, $customer)
    {
        return $this->newQuery()
                    ->where(Entity::METHOD, '=', $method)
                    ->where(Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(Entity::MERCHANT_ID, '=', $customer->merchant->getId())
                    ->get();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}
