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

    public function add($uid, $input)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $token = (new Token\Core)->create($customer, $input);

        return $token->toArrayPublic();
    }

    public function edit($uid, $mid, $input)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $token = $this->tokensRepo->getByIdAndCustomerId($uid, $mid);

        $token = (new Token\Core)->edit($token, $input);

        return $token->toArrayPublic();
    }

    public function fetch($uid, $mid)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $token = $this->tokensRepo->getByIdAndCustomerId($uid, $mid);

        return $token->toArrayPublic();
    }

    public function fetchMultiple($uid)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $tokens = $this->tokensRepo->getByCustomerId($uid);

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

    public function delete($uid, $mid)
    {
        $token = $this->tokensRepo->findOrFailPublic($mid);

        assert($token->getCustomerId() === $uid);

        $token = $this->tokensRepo->deleteOrFail($token);

        if ($token === null)
            return [];

        return $token->toArrayPublic();
    }

    public function deleteAppToken($appId, $mId)
    {
        $app = (new Customer\App\Repository)->findByAppIdAndMerchantId($appId, $this->merchant->getId());

        return $this->delete($app->getCustomerId(), $mId);
    }
}
