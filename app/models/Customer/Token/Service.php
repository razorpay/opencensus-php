<?php

namespace Models\Customer\Token;

use EE\Exception;
use Models\Base;
use Models\Customer;
use Models\Customer\Token;
use Models\Merchant\Account;

class Service extends Base\Service
{
    /**
     * Note that this is on internal auth and not private auth
     */
    public function add($id, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findOrFailPublic($id);

        $token = (new Token\Core)->create($customer, $input);

        return $token->toArrayPublic();
    }

    public function edit($id, $token, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $token = $this->repo->token->getByTokenAndCustomerId($id, $token);

        $token = (new Token\Core)->edit($token, $input);

        return $token->toArrayPublic();
    }

    public function fetch($id, $token)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $token = $this->repo->token->getByTokenAndCustomerId($customer->getId(), $token);

        return $token->toArrayPublic();
    }

    public function fetchMultiple($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $tokens = $this->repo->token->getByCustomerId($id);

        return $tokens->toArrayPublic();
    }

    public function fetchTokensByAppToken($appToken)
    {
        Customer\App\Entity::verifyIdAndStripSign($appToken);

        $tokens = (new Customer\Token\Core)->fetchTokensByAppToken($this->merchant->getKey(), $appToken);

        return $tokens->toArrayPublic();
    }

    public function delete($id, $token, $merchantId = null)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        if ($merchantId === null)
        {
            $merchantId = $this->merchant->getId();
        }

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $merchantId);

        $token = $this->repo->token->getByTokenAndCustomerId($id, $token);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        $token = $this->repo->token->deleteOrFail($token);

        if ($token === null)
        {
            return ['deleted' => true];
        }

        return $token->toArrayPublic();
    }

    public function deleteTokenForApp($appToken, $token)
    {
        Customer\App\Entity::verifyIdAndStripSign($appToken);

        $app = (new Customer\App\Repository)->findByIdAndMerchantId($appToken, $this->merchant->getId());

        $customerId = $app->customer->getPublicId();

        return $this->delete($customerId, $token, Account::SHARED_ACCOUNT);
    }
}
