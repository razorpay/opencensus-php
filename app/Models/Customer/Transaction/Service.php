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

    public function getStatement(Customer\Balance\Entity $customerBalance, array $input = []) : array
    {
        $input[Entity::CUSTOMER_ID] = $customerBalance->getCustomerId();

        $entities = $this->repo
                         ->customer_transaction
                         ->fetchCustomerStatement(
                            $input, $this->merchant);

        return $entities->toArrayPublic();
    }

    public function createForRefund(array $input) : string
    {
        return $this->repo->transaction(function () use ($input)
        {
            $customerTxn = $this->core
                                ->createFromCustomerRefund($input);

            return $customerTxn->getId();
        });
    }

    public function createForDebit(array $input) : string
    {
        return $this->repo->transaction(function () use ($input)
        {
            $customerTxn = $this->core
                                ->createForCustomerDebit($input);

            return $customerTxn->getId();
        });
    }
}
