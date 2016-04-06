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
        $customer = $this->repo->findOrFailPublic($id);

        $customer= (new Customer\Core)->edit($customer, $input);

        return $customer->toArrayPublic();
    }

    public function fetch($id)
    {
        $customer = $this->repo->findOrFailPublic($id);

        return $customer->toArrayPublic();
    }

    public function delete($id)
    {
        $customer = $this->repo->findOrFail($id);

        $customer = $this->repo->deleteOrFail($customer);

        if ($customer === null)
            return [];

        return $customer->toArrayPublic();
    }

    public function sendOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['service'] = 'api';

        $data = (new Customer\Raven)->sendOtp($input);

        return $data;
    }

    public function verifyOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['service'] = 'api';

        $data = (new Customer\Core)->verifyOtp($input);

        return $data;
    }

    public function updateSmsStatus($service, $input)
    {
        $data = (new Customer\Raven)->updateSmsStatus($service, $input);

        return $data;
    }

    protected function createCustomer($input)
    {
        $customer = (new Customer\Core)->create($input);

        return $customer->toArrayPublic();
    }
}

