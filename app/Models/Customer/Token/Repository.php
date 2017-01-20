<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Customer\Token;
use RZP\Exception;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'token';

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

    public function getByCustomer($customer)
    {
        return $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }

    public function getGlobalOrLocalTokenEntityOfPayment($payment)
    {
        $token = null;

        if ($payment->getTokenId() !== null)
        {
            $token = $this->findOrFail($payment->getTokenId());
            $payment->localToken()->associate($token);
        }
        else if ($payment->getGlobalTokenId() !== null)
        {
            $token = $this->findOrFail($payment->getGlobalTokenId());
            $payment->globalToken()->associate($token);
        }

        return $token;
    }

    public function getByTokenIdAndCustomer($tokenId, Customer\Entity $customer)
    {
        $token = $this->newQuery()
                    ->where(Token\Entity::CUSTOMER_ID, '=', $customer->getId())
                    ->where(Token\Entity::TOKEN, '=', $tokenId)
                    ->first();

        if ($token !== null)
        {
            $token->customer()->associate($customer);
        }

        return $token;
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
