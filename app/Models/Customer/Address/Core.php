<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;
use RZP\Models\Customer;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create(Customer\Entity $customer, array $input)
    {
        // TODO: Run the validations. Source it to the customer. Get this piece of code from transactions.

        $address = (new Entity)->build($input);

        $this->repo->saveOrFail($address);

        return $address;
    }
}