<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Models\Base;

/**
 * Class Repository
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Repository extends Base\Repository
{
    protected $entity = 'sub_virtual_account';

    /**
     * Get Sub Virtual Account if exists with similar details.
     * @param  array           $input
     * @return Entity|null
     */
    public function getSubVirtualAccountOfTypeDefaultWithSimilarDetails(array $input)
    {
        return $this->newQuery()
                    ->where(Entity::MASTER_ACCOUNT_NUMBER, $input[Entity::MASTER_ACCOUNT_NUMBER])
                    ->where(Entity::SUB_ACCOUNT_NUMBER, $input[Entity::SUB_ACCOUNT_NUMBER])
                    ->where(Entity::SUB_ACCOUNT_TYPE, $input[Entity::SUB_ACCOUNT_TYPE])
                    ->first();
    }

    /**
     * Get Sub Virtual Account if exists with similar details
     * using master merchant id, master account number and
     * sub account number.
     * @param  array           $input
     * @return Entity|null
     */
    public function getSubVirtualAccountWithMasterMerchantIdAndAccountNumbers(array $input, $masterMerchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MASTER_MERCHANT_ID, $masterMerchantId)
                    ->where(Entity::MASTER_ACCOUNT_NUMBER, $input[Entity::MASTER_ACCOUNT_NUMBER])
                    ->where(Entity::SUB_ACCOUNT_NUMBER, $input[Entity::SUB_ACCOUNT_NUMBER])
                    ->first();
    }

    public function getSubVirtualAccountFromSubAccountNumber($subAccountNumber, $filterActiveOnly = true)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::SUB_ACCOUNT_NUMBER, '=', $subAccountNumber);

        if ($filterActiveOnly === true)
        {
            $query->where(Entity::ACTIVE, '=', 1);
        }

        return $query->first();
    }

    public function getSubVirtualAccountsFromMasterMerchantId($masterMerchantId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::MASTER_MERCHANT_ID, '=', $masterMerchantId)
                    ->where(Entity::SUB_ACCOUNT_TYPE, '=', Type::SUB_DIRECT_ACCOUNT)
                    ->get();
    }
}
