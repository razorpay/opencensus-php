<?php

namespace RZP\Models\BankAccount;

use RZP\Exception;
use RZP\Models\BankAccount;
use RZP\Models\Base;
use RZP\Models\VirtualAccount;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'bank_account';

    const WITH_TRASHED = 'deleted';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:customer,merchant',
        Entity::ENTITY_ID       => 'sometimes|alpha_num'
    );

    public function getBankAccount($merchant)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $merchant->getId())
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->first();
    }

    /**
     * Returns an array of all the bank accounts for a merchant
     *
     * @param $merchant
     *
     * @return array
     */
    public function getAllBankAccounts($merchant)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $merchant->getId())
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->get()
                    ->toArray();
    }

    public function getBankAccountsForCustomer($customer, $ifsc = null)
    {
        $query = $this->newQuery()
                      ->where(Entity::ENTITY_ID, '=', $customer->getId())
                      ->where(Entity::TYPE, '=', Type::CUSTOMER);

        if ($ifsc !== null)
        {
            $query->where(Entity::IFSC_CODE, 'like', '%'.$ifsc.'%');
        }

        return $query->get();
    }

    public function getRazorpayBankAccountsForCustomer($customer, $ifsc = 'RAZR')
    {
        return $this->getBankAccountsForCustomer($customer, $ifsc);
    }

    public function getBankAccountsFromAccountNumber($accountNumber, $ifsc = null)
    {
        $query = $this->newQuery()
                      ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                      ->where(Entity::TYPE, '=', Type::CUSTOMER);

        if ($ifsc !== null)
        {
            $query->where(Entity::IFSC_CODE, 'like', '%'.$ifsc.'%');
        }

        return $query->get();
    }

    public function findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode = null)
    {
        $virtualAccountId     = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::ID);
        $virtualAccountStatus = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::STATUS);

        $bankAccountEntityId = $this->dbColumn(Entity::ENTITY_ID);
        $bankAccountType     = $this->dbColumn(Entity::TYPE);
        $bankAccountData     = $this->dbColumn('*');

        $query = $this->newQuery()
                      ->select($bankAccountData)
                      ->join(Table::VIRTUAL_ACCOUNT, $bankAccountEntityId, '=', $virtualAccountId)
                      ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                      ->where(Entity::TYPE, '=', Type::VIRTUAL_ACCOUNT)
                      ->where($virtualAccountStatus, '=', VirtualAccount\Status::ACTIVE);

        if ($bankCode !== null)
        {
            $query->where(Entity::IFSC_CODE, 'like', $bankCode.'%');
        }

        return $query->first();
    }

    public function getRazarpayBankAccountsFromAccountNumber($accountNumber)
    {
        $ifsc = 'RAZR';

        return $this->getBankAccountsFromAccountNumber($accountNumber, $ifsc);
    }

    public function findByCustomerIdAndAccountNumber($customerId, $accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $customerId)
                    ->where(Entity::TYPE, '=', Type::CUSTOMER)
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->get();
    }

    public function findFirstBankAccountByAccountNumber($accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->first();
    }

    public function getAllOrderedByCreatedAt()
    {
        return $this->newQuery()
                    ->oldest()
                    ->get();
    }

    public function fetchBankAccountsWithoutBeneCode()
    {
        return $this->newQuery()
                    ->where(BankAccount\Entity::TYPE, '=', BankAccount\Type::MERCHANT)
                    ->whereNull(BankAccount\Entity::BENEFICIARY_CODE)
                    ->take(1000)
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

    protected function addQueryParamDeleted($query, $params)
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
