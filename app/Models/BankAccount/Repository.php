<?php

namespace RZP\Models\BankAccount;

use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'BankAccount';

    const WITH_TRASHED = 'with_trashed';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:customer,merchant',
    );

    public function getBankAccount($merchant)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $merchant->getId())
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->first();
    }

    public function getBankAccountsForCustomer($customer)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $customer->getId())
                    ->where(Entity::TYPE, '=', Type::CUSTOMER)
                    ->get();
    }

    public function getAllOrderedByCreatedAt()
    {
        return $this->newQuery()
                    ->oldest()
                    ->get();
    }

    public function getAllActivatedMerchantAccountsOrderedByCreatedAt()
    {
        return $this->newQuery()
                    ->where(BankAccount\Entity::TYPE, '=', BankAccount\Type::MERCHANT)
                    ->oldest()
                    ->get();
    }

    public function getMerchantBankAccountsBetweenTimestamp($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(BankAccount\Entity::CREATED_AT, array($from, $to))
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->oldest()
                    ->get();
    }

    public function fetchByEntityIdAndType($entityId, $type, $merchantId)
    {
        return $this->newQuery()
                    ->where(BankAccount\Entity::TYPE, '=', $type)
                    ->where(BankAccount\Entity::ENTITY_ID, '=', $entityId)
                    ->where(BankAccount\Entity::MERCHANT_ID, '=', $merchantId)
                    ->oldest()
                    ->get();
    }

    public function getCountOfBankAccountsCreatedBetween($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(BankAccount\Entity::CREATED_AT, array($from, $to))
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->count();
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    protected function addQueryParamWithTrashed($query, $params)
    {
        if ($params[self::WITH_TRASHED] === '1')
        {
            $query->withTrashed();
        }
    }

    /**
     * This should be called when deleting a BankAccount Entity.
     *
     * This checks if the bankAccount has any settlements linked to it.
     * If there are linked settlements then it is soft deleted.
     * Else, it is hard deleted.
     *
     * @param  BankAccount\Entity $bankAccount The bank account to be deleted
     */
    public function delete($bankAccount)
    {
        if ($bankAccount->settlements->count() === 0)
        {
            return $bankAccount->forceDelete();
        }
        else
        {
            return $bankAccount->delete();
        }
    }
}