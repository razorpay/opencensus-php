<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer;
use Models\Merchant\Account;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Customer\Repository;
    }

    public function create($input)
    {
        $input['merchant_id'] = $this->merchant->getId();

        return $this->createCustomer($input);
    }

    public function createGlobalCustomer($input)
    {
        $input['merchant_id'] = Account::SHARED_ACCOUNT;

        return $this->createCustomer($input);
    }

    public function edit($id, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        $customer = (new Customer\Core)->edit($customer, $input);

        return $customer->toArrayPublic();
    }

    public function fetch($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        return $customer->toArrayPublic();
    }

    public function delete($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        $customer = $this->repo->deleteOrFail($customer);

        if ($customer === null)
            return [];

        return $customer->toArrayPublic();
    }

    public function sendOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['source'] = 'api';

        $data = (new Customer\Raven)->sendOtp($input);

        return $data;
    }

    public function verifyOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['source'] = 'api';

        $data = (new Customer\Core)->verifyOtp($input);

        return $data;
    }

    public function updateSmsStatus($id, $input)
    {
        $data = (new Customer\Raven)->updateSmsStatus($id, $input);

        return $data;
    }

    protected function createCustomer($input)
    {
        $customer = (new Customer\Core)->create($input);

        return $customer->toArrayPublic();
    }
}

