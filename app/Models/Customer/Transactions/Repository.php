<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'customer_transactions';

    protected $entityFetchParamRules = [
        'customer_id'  => 'required|string|size:19'
    ];

    public function fetchCustomerStatement($input, $customerId, $merchantId)
    {
        $input[Entity::CUSTOMER_ID] = $customerId;

        $records = $this->fetch($input, $merchantId);

        return $records;
    }

    protected function addQueryParamCustomerId($query, $input)
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        Customer\Entity::verifyIdAndStripSign($customerId);

        $query->where(Entity::CUSTOMER_ID, $customerId);
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }
}
