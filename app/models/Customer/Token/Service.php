<?php

namespace Models\Customer\Token;

use EE\Exception;
use Models\Base;
use Models\Customer;
use Models\Customer\Token;
use Models\Merchant\Account;

class Service extends Base\Service
{
    protected $repo;
    protected $tokensRepo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Customer\Repository;

        $this->tokensRepo = new Token\Repository;
    }

    /**
     * Note that this is on internal auth and not private auth
     */
    public function add($id, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findOrFailPublic($id);

        $token = (new Token\Core)->create($customer, $input);

        return $token->toArrayPublic();
    }

    public function edit($id, $token, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        $token = $this->tokensRepo->getByTokenAndCustomerId($id, $token);

        $token = (new Token\Core)->edit($token, $input);

        return $token->toArrayPublic();
    }

    public function fetch($id, $token)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        $token = $this->tokensRepo->getByTokenAndCustomerId($id, $token);

        return $token->toArrayPublic();
    }

    public function fetchMultiple($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        $tokens = $this->tokensRepo->getByCustomerId($id);

        return $tokens->toArrayPublic();
    }

    public function fetchTokensByAppId($appId)
    {
        Customer\App\Entity::verifyIdAndStripSign($appId);

        $tokens = (new Customer\Token\Core)->fetchTokensByAppId($this->merchant->getKey(), $appId);

        return $tokens->toArrayPublic();
    }

    public function fetchCustomerStatus($contact)
    {
        $saved = false;

        $customer = $this->repo->findByContactForMerchant($contact, Account::SHARED_ACCOUNT);

        if ($customer !== null)
        {
            $tokens = (new Customer\Token\Core)->fetchTokensByCustomerId(
                Account::SHARED_ACCOUNT, $customer->getId());

            if ($tokens !== null)
            {
                $saved = true;
            }
        }

        $result = array(
            'saved' =>  $saved
        );

        return $result;
    }

    public function delete($id, $token, $merchantId = null)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        if ($merchantId === null)
        {
            $merchantId = $this->merchant->getId();
        }

        $customer = $this->repo->findByIdAndMerchantId($id, $merchantId);

        $token = $this->tokensRepo->getByTokenAndCustomerId($id, $token);

        if ($token === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token not found');
        }

        $token = $this->tokensRepo->deleteOrFail($token);

        if ($token === null)
        {
            return ['deleted' => true];
        }

        return $token->toArrayPublic();
    }

    public function deleteAppToken($appId, $token)
    {
        Customer\App\Entity::verifyIdAndStripSign($appId);

        $app = (new Customer\App\Repository)->findByIdAndMerchantId($appId, $this->merchant->getId());

        $customerId = $app->customer->getPublicId();

        return $this->delete($customerId, $token, Account::SHARED_ACCOUNT);
    }
}
