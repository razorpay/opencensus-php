<?php 

namespace Models\Customer;

use Models\Base;
use Models\Customer;

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

        $customer = (new Customer\Core)->create($input);

        return $customer->toArrayPublic();
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
}
