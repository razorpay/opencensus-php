<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Customer\Repository;
    }

    public function create($input)
    {
        $customer = (new Customer\Entity)->build($input);

        $this->repo->saveOrFail($customer);

        return $customer;
    }

    public function edit($customer, $input)
    {        
        $customer->edit($input);

        $this->repo->saveOrFail($customer);

        $this->trace->info(
            TraceCode::CUSTOMER_EDIT,
            [$input]);

        return $customer;
    }
}