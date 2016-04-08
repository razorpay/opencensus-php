<?php

namespace Models\Customer\Token;

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

    public function add($id, $input)
    {
        $customer = $this->repo->findOrFailPublic($id);

        $token = (new Token\Core)->create($customer, $input);

        return $token->toArrayPublic();
    }

    public function edit($id, $token, $input)
    {
        $customer = $this->repo->findOrFailPublic($id);

        $token = $this->tokensRepo->getByTokenAndCustomerId($id, $token);

        $token = (new Token\Core)->edit($token, $input);

        return $token->toArrayPublic();
    }

    public function fetch($id, $token)
    {
        $customer = $this->repo->findOrFailPublic($id);

        $token = $this->tokensRepo->getByTokenAndCustomerId($id, $token);

        return $token->toArrayPublic();
    }

    public function fetchMultiple($id)
    {
        $customer = $this->repo->findOrFailPublic($id);

        $tokens = $this->tokensRepo->getByCustomerId($id);

        return $tokens->toArrayPublic();
    }

    public function fetchTokensByAppId($appId)
    {
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

    public function delete($id, $token)
    {
        $token = $this->tokensRepo->getByTokenAndCustomerId($id, $token);

        $token = $this->tokensRepo->deleteOrFail($token);

        if ($token === null)
            return [];

        return $token->toArrayPublic();
    }

    public function deleteAppToken($appId, $token)
    {
        $app = (new Customer\App\Repository)->findByAppIdAndMerchantId($appId, $this->merchant->getId());

        return $this->delete($app->getCustomerId(), $token);
    }
}
