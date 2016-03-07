<?php

namespace Models\Customer\Methods;

use Models\Base;
use Models\Customer;
use Models\Customer\Methods;

class Service extends Base\Service
{
    protected $repo;
    protected $methodsRepo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Customer\Repository;

        $this->methodsRepo = new Methods\Repository;
    }

    public function add($uid, $input)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $method = (new Methods\Core)->create($customer, $input);

        return $method->toArrayPublic();

    }

    public function edit($uid, $mid, $input)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $method = $this->methodsRepo->getByIdAndCustomerId($uid, $mid);

        $method = (new Methods\Core)->edit($method, $input);

        return $method->toArrayPublic();
    }

    public function fetch($uid, $mid)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $method = $this->methodsRepo->getByIdAndCustomerId($uid, $mid);

        return $method->toArrayPublic();

    }

    public function fetchMultiple($uid)
    {
        $customer = $this->repo->findOrFailPublic($uid);

        $methods = $this->methodsRepo->getByCustomerId($uid);

        return $methods->toArrayPublic();
    }

    public function delete($uid, $mid)
    {
        $method = $this->methodsRepo->findOrFailPublic($mid);

        $method = $this->methodsRepo->deleteOrFail($method);

        if ($method === null)
            return [];

        return $method->toArrayPublic();
    }
}
 