<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Account;
use RZP\Exception;

class Service extends Base\Service
{
    /**
     * Note that this is on internal auth and not private auth
     * Adds token for a customer
     * @param string customer_id
     * @param array customer token params
     */
    public function add($id, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $token = (new Token\Core)->createToken($customer, $input);

        return $token->toArrayPublic();
    }

    /**
     * Edit an existing token for local customer
     * @param  string customer_id
     * @param  entity token
     * @param  array  token edit params
     * @return array  edited token
     */
    public function edit($id, $token, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $token = $this->repo->token->getByTokenAndCustomerId($token, $id);

        $token = (new Token\Core)->edit($token, $input);

        return $token->toArrayPublic();
    }

    /**
     * fetch token for local customer
     *
     * @param  string customer_id
     * @param  string token id
     * @return entity token
     */
    public function fetch($id, $token)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $token = $this->repo->token->getByTokenAndCustomerId($token, $customer->getId());

        return $token->toArrayPublic();
    }

    /**
     * fetch tokens for local customer
     * @param  string customer_id
     * @return entity tokens
     */
    public function fetchMultiple($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $tokens = $this->repo->token->getByCustomerId($id);

        return $tokens->toArrayPublic();
    }

    /**
     * fetch tokens for an app_token (global customer)
     * @param  string app_token
     * @return entity tokens
     */
    public function fetchTokensForGlobalCustomer()
    {
        $appToken = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        $tokens = new Base\PublicCollection;

        if ($appToken !== null)
        {
            AppToken\Entity::verifyIdAndStripSign($appToken);

            $app = (new AppToken\Core)->getAppByAppToken($appToken, $this->merchant);

            $tokens = (new Customer\Token\Core)->fetchTokensByCustomer($app->customer);
        }

        return $tokens->toArrayPublic();
    }

    /**
     * Deletes tokens associated with the local customer
     */
    public function deleteTokenForLocalCustomer($id, $token)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        return $this->deleteTokenForCustomer($token, $customer);
    }

    /**
     * Deletes token associated with a card for a global customer
     */
    public function deleteTokenForGlobalCustomer($token)
    {
        $appToken = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        if ($appToken !== null)
        {
            AppToken\Entity::verifyIdAndStripSign($appToken);

            $app = (new AppToken\Core)->getAppByAppToken($appToken, $this->merchant);

            return $this->deleteTokenForCustomer($token, $app->customer);
        }
    }

    protected function deleteTokenForCustomer($token, $customer)
    {
        $token = $this->repo->token->getByTokenAndCustomerId($token, $customer->getId());

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
}
