<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Models\Base;
use RZP\Models\Customer;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function getStatement($customerId, $input)
    {
        $input[Entity::CUSTOMER_ID] = $customerId;

        (new Validator)->validateInput('get_statement', $input);

        $entities = $this->repo->customer_transaction
                    ->fetchCustomerStatement($input, $customerId, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function createForRefund($customerId, $amount)
    {
        $this->repo->transaction(function () use ($customerId, $amount)
        {
            $customerTxn = $this->core->createFromCustomerRefund($customerId, $amount);

            $this->repo->saveOrFail($customerTxn);
        });
    }
}
