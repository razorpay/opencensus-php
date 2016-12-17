<?php

namespace RZP\Models\Customer\Transaction;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'customer_transaction';

    protected $entityFetchParamRules = [
        'customer_id'  => 'sometimes|string|size:14'
    ];

    /**
     * Fetches transaction statement for a customer balance account
     * Common pagination params apply (count, skip, from, to)
     *
     * @param  array    $input      Input Params
     * @param  string   $merchantId
     * @return array
     */
    public function fetchCustomerStatement(array $input, string $merchantId)
    {
        $records = $this->fetch($input, $merchantId);

        return $records;
    }

    protected function addQueryParamCustomerId($query, $input)
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        $query->where(Entity::CUSTOMER_ID, $customerId);
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    /**
     * Fetch the last customer_transaction credit,
     * that is not a refund txn
     *
     * @param  string $customerId
     * @param  string $merchantId
     * @return Entity
     */
    public function fetchLastCreditTransaction(string $customerId, string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::CUSTOMER_ID, $customerId)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::TYPE, '!=', Type::REFUND)
                    ->where(Entity::DEBIT, 0)
                    ->where(Entity::CREDIT, '>', 0)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }
}
