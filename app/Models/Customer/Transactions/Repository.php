<?php

namespace RZP\Models\Customer\Transactions;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'customer_transactions';

    protected $entityFetchParamRules = [
        'customer_id'  => 'sometimes|string|size:19'
    ];


    /**
     * Fetches transaction statement for a customer balance account
     * Common pagination params apply (count, skip, from, to)
     *
     * @param  array    $input      Input Params
     * @param  string   $customerId
     * @param  string   $merchantId
     * @return array
     */
    public function fetchCustomerStatement($input, $customerId, $merchantId)
    {
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
