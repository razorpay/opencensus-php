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

    public function getStatement(string $customerId, array $input = []) : array
    {
        $input[Entity::CUSTOMER_ID] = $customerId;

        (new Validator)->validateInput('get_statement', $input);

        $entities = $this->repo
                         ->customer_transaction
                         ->fetchCustomerStatement(
                            $input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function createForRefund(array $input)
    {
        return $this->repo->transaction(function () use ($input)
        {
            $amount = $input['amount'];

            $customerId = $input['payment']['customer_id'];

            $refundId = $input['refund']['id'];

            $customerTxn = $this->core
                                ->createFromCustomerRefund(
                                    $customerId, $refundId, $amount);

            $this->repo->saveOrFail($customerTxn);

            return $customerTxn->getId();
        });
    }
}
