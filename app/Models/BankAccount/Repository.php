<?php

namespace RZP\Models\BankAccount;

use DB;

use RZP\Models\Base;
use Rzp\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'bank_account';

    const WITH_TRASHED = 'deleted';

    protected $appFetchParamRules = [
        Entity::ACCOUNT_NUMBER  => 'sometimes|alpha_num',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        self::WITH_TRASHED      => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|in:customer,merchant',
        Entity::ENTITY_ID       => 'sometimes|alpha_num'
    ];

    public function getBankAccount($merchant, $type=null)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $merchant->getId())
                    ->where(Entity::TYPE, '=', $type ?? Type::MERCHANT)
                    ->first();
    }

    public function getOrgBankAccount($orgId, $type=null)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $orgId)
            ->where(Entity::TYPE, '=', $type ?? Type::ORG)
            ->first();
    }

    public function getBankAccountOnConnection($merchant, string $mode)
    {
        return $this->newQueryWithConnection($mode)
                    ->where(Entity::ENTITY_ID, '=', $merchant->getId())
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->first();
    }

    public function getBankAccountsForMerchants(array $mids, $columns = ['*'])
    {
        return $this->newQuery()
                    ->select($columns)
                    ->whereIn(Entity::ENTITY_ID, $mids)
                    ->where(Entity::TYPE, Type::MERCHANT)
                    ->get();
    }

    public function getSettlementAccountDetails($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::TYPE, Type::MERCHANT)
                    ->where(function ($query) {
                            $query->where(Entity::IFSC_CODE, '=', VirtualAccount\Provider::IFSC[VirtualAccount\Provider::YESBANK])
                                  ->orWhere(Entity::IFSC_CODE, '=', VirtualAccount\Provider::IFSC[VirtualAccount\Provider::ICICI]);
                            })
                    ->first();
    }

    public  function getXVirtualAccountOrFail($merchantId, $accountNumber, $ifscCode)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::TYPE, Type::VIRTUAL_ACCOUNT)
                    ->where(Entity::ACCOUNT_NUMBER,'=', $accountNumber)
                    ->where(Entity::IFSC_CODE,'=', $ifscCode)
                    ->firstOrFail();
    }

    /**
     * Returns an array of all the bank accounts for a merchant
     *
     * @param $merchant
     *
     * @return PublicCollection
     */
    public function getAllBankAccounts($merchant): PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $merchant->getId())
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->get();
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

    public function getBankAccountsForOrder($orderId)
    {
        $query = $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $orderId)
            ->where(Entity::TYPE, '=', Type::ORDER);

        return $query->first();
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

    public function findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $bankCode = null, bool $withTrashed = false)
    {
        $virtualAccountId     = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::ID);

        $bankAccountEntityId = $this->dbColumn(Entity::ENTITY_ID);
        $bankAccountData     = $this->dbColumn('*');

        $query = $this->newQuery()
                      ->select($bankAccountData)
                      ->join(Table::VIRTUAL_ACCOUNT, $bankAccountEntityId, '=', $virtualAccountId)
                      ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                      ->where(Entity::TYPE, '=', Type::VIRTUAL_ACCOUNT)
                      ->latest();

        if ($bankCode !== null)
        {
            $query->where(Entity::IFSC_CODE, 'like', $bankCode.'%');
        }

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
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

    public function getAllActivatedMerchantAccountsOrderedByCreatedAt(array $merchantIds)
    {
        $query = $this->newQuery()
                      ->where(BankAccount\Entity::TYPE, '=', BankAccount\Type::MERCHANT)
                      ->with(['source', 'source.merchantDetail'])
                      ->oldest();

        if (empty($merchantIds) === false)
        {
            $query->whereIn(BankAccount\Entity::MERCHANT_ID, $merchantIds);
        }

        return $query->get();
    }

    public function getBankAccountsBetweenTimestamp($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(BankAccount\Entity::CREATED_AT, [$from, $to])
                    ->whereIn(Entity::TYPE, Type::getBeneficiaryRegistrationTypes())
                    ->with(['source'])
                    ->oldest()
                    ->get();
    }

    public function getMerchantBankAccountsBetweenTimestamp($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(BankAccount\Entity::CREATED_AT, [$from, $to])
                    ->where(Entity::TYPE, '=', Type::MERCHANT)
                    ->with(['source'])
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

    public function fetchBankAccountByTypeCustomerAndApplyLimit($entityId, $merchantId, $limit)
    {
        return $this->newQuery()
                    ->where(BankAccount\Entity::TYPE, '=', Type::CUSTOMER)
                    ->where(BankAccount\Entity::ENTITY_ID, '=', $entityId)
                    ->where(BankAccount\Entity::MERCHANT_ID, '=', $merchantId)
                    ->latest()
                    ->limit($limit)
                    ->get();
    }

    public function getCountOfBankAccountsCreatedBetween($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(BankAccount\Entity::CREATED_AT, [$from, $to])
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
     * this will normalize the given string to bene code format if applicable
     * Beneficiary code is generated by using the first 7 + last 3 of
     * Bank account number id.
     * In case the search value provided to the bene code section is 14 char
     * then convert it to bene code format as mentioned above.
     * else use the same value as provided
     *
     * @param       $query
     * @param array $params
     */
    protected function addQueryParamBeneficiaryCode($query, array $params)
    {
        if (empty($params[Entity::BENEFICIARY_CODE]) === true)
        {
            return;
        }

        $id = $params[Entity::BENEFICIARY_CODE];

        $first7 = substr($id, 0, 7);

        $last3 = substr($id, -3);

        $beneficiaryCode = $first7 . $last3;

        $beneficiaryCode = strtoupper($beneficiaryCode);

        assertTrue(strlen($beneficiaryCode) === 10);

        $query->where(Entity::BENEFICIARY_CODE, $beneficiaryCode);
    }

    /**
     * This should be called when deleting a BankAccount Entity.
     *
     * This checks if the bankAccount has any settlements linked to it.
     * If there are linked settlements then it is soft deleted.
     * Else, it is hard deleted.
     *
     * @param  BankAccount\Entity $bankAccount The bank account to be deleted
     *
     * @return bool|null
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

    /**
     * Fetches the details of bank account for the given id if active
     *
     * @param string $bankAccountId
     * @return mixed
     */
    public function getBankAccountById(string $bankAccountId)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $bankAccountId)
                    ->first();
    }

    public function getBankAccountByIdCustomerIdAndMerchantId(string $bankAccountId, string $customerId, string $merchantId)
    {
        return $this->newQuery()->withTrashed()
            ->where(Entity::ID, $bankAccountId)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::ENTITY_ID, $customerId)
            ->where(Entity::TYPE, '=', 'customer')
            ->first();
    }

    public function isBankAccountChanged(Entity $bankAccount): bool
    {
        return $this->newQuery()
                    ->withTrashed()
                    ->whereNotNull(Entity::DELETED_AT)
                    ->where(Entity::ID, '<', $bankAccount->getId())
                    ->where(Entity::ENTITY_ID, '=', $bankAccount->getEntityId())
                    ->where(Entity::TYPE, '=', 'merchant')
                    ->exists();
    }

    /**
     * @param string $accountNumber
     * @param string $ifscCode
     * @param string $type
     * @param string $name
     * @param string $merchantId
     * @return Entity|null
     */
    public function findLatestBankAccountByAccountNumber(
        string $accountNumber,
        string $ifscCode,
        string $name,
        string $type,
        string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::IFSC_CODE, $ifscCode)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::BENEFICIARY_NAME, $name)
                    ->merchantId($merchantId)
                    ->latest()
                    ->first();
    }

    /**
     * Fetches the details of bank account for the given
     * fts_fund_account_id
     *
     * @param string $ftsFundAccountId
     * @return mixed
     */
    public function getBankAccountByFtsFundAccountId(string $ftsFundAccountId)
    {
        return $this->newQuery()
                    ->where(Entity::FTS_FUND_ACCOUNT_ID, $ftsFundAccountId)
                    ->first();
    }

    /**
     * Fetches bank accounts which are not present in banking account table
     * for balance type banking
     *
     * @param string $limit
     * @return mixed
     */
    public function fetchAccountsNotPresentInBankingAccountsForYesbank(string $limit)
    {
        //
        // SELECT `bank_accounts`.*
        //   FROM `bank_accounts`
        // INNER JOIN `virtual_accounts` ON `virtual_accounts`.`bank_account_id` = `bank_accounts`.`id`
        // INNER JOIN `balance` ON `balance`.`id` = `virtual_accounts`.`balance_id`
        // WHERE `balance`.`type` = 'banking'
        //   AND `balance`.`id` NOT IN
        //       (
        //           SELECT `banking_accounts`.`balance_id`
        //             FROM `banking_accounts`
        //            WHERE `banking_accounts`.`balance_id` IS NOT NULL
        //       )
        //   AND `bank_accounts`.`deleted_at` IS NULL
        //

        $bankAccountColumns = $this->dbColumn('*');

        $bankAccountId = $this->dbColumn(Entity::ID);

        $balanceRepo = $this->repo->balance;

        $balanceType = $balanceRepo->dbColumn(Balance\Entity::TYPE);

        $balanceId = $balanceRepo->dbColumn(Balance\Entity::ID);

        $virtualAccountRepo = $this->repo->virtual_account;

        $virtualAccountBankAccountId = $virtualAccountRepo->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID);

        $virtualAccountBalanceId = $virtualAccountRepo->dbColumn(VirtualAccount\Entity::BALANCE_ID);

        $bankingAccountRepo = $this->repo->banking_account;

        $bankingAccountTableName = $bankingAccountRepo->getTableName();

        $bankingAccountBalanceId = $bankingAccountRepo->dbColumn(BankingAccount\Entity::BALANCE_ID);

        return $this->newQuery()
                    ->select($bankAccountColumns)
                    ->join($virtualAccountRepo->getTableName(), $virtualAccountBankAccountId, '=', $bankAccountId)
                    ->join($balanceRepo->getTableName(), $balanceId, '=', $virtualAccountBalanceId)
                    ->where($balanceType, '=', 'banking')
                    ->whereNotIn(
                        $balanceId,
                        function($query)
                        use ($bankingAccountBalanceId,
                            $bankingAccountTableName)
                        {
                            $query->select($bankingAccountBalanceId)
                                  ->from($bankingAccountTableName)
                                  ->whereNotNull($bankingAccountBalanceId);
                        })
                    ->limit($limit)
                    ->orderByCreatedAt()
                    ->get();
    }

    // Not really sure if the method should be called as fetchExistingBankAccount ,
    // in case you realise it can't be reused for your use case please modify it
    // accordingly and also rename it at correctly from different places it is
    // being called from.
    public function fetchBankAccount(
        Merchant\Entity $merchant,
        $input)
    {
        $name = $input[Entity::BENEFICIARY_NAME] ?? null;

        $accountNumber = $input[Entity::ACCOUNT_NUMBER] ?? null;

        $ifsc = $input[Entity::IFSC_CODE] ?? null;

        $entityId = $input[Entity::ENTITY_ID] ?? null;

        $entityType = $input[Entity::TYPE] ?? null;

        return $this->newQuery()
                    ->where(Entity::BENEFICIARY_NAME, $name)
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::IFSC_CODE, $ifsc)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::TYPE, $entityType)
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->first();
    }

    public function checkIfBankAccountBelongsToWhitelistedMerchant(Entity $bankAccount,
                                                                   array $destinationMIDsToWhitelist): bool
    {
        $typeColumn          = $this->repo->bank_account->dbColumn(Entity::TYPE);
        $entityIdColumn      = $this->repo->bank_account->dbColumn(Entity::ENTITY_ID);
        $ifscCodeColumn      = $this->repo->bank_account->dbColumn(Entity::IFSC_CODE);
        $merchantIdColumn    = $this->repo->bank_account->dbColumn(Entity::MERCHANT_ID);
        $accountNumberColumn = $this->repo->bank_account->dbColumn(Entity::ACCOUNT_NUMBER);

        $virtualAccountTable             = $this->repo->virtual_account->getTableName();
        $virtualAccountIdColumn          = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::ID);
        $virtualAccountTableStatusColumn = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::STATUS);

        return $this->newQuery()
                    ->join($virtualAccountTable, $entityIdColumn, '=', $virtualAccountIdColumn)
                    ->where($accountNumberColumn, '=', $bankAccount->getAccountNumber())
                    ->where($ifscCodeColumn, '=', $bankAccount->getIfscCode())
                    ->where($typeColumn, '=', Type::VIRTUAL_ACCOUNT)
                    ->where($virtualAccountTableStatusColumn, '=', VirtualAccount\Status::ACTIVE)
                    ->whereIn($merchantIdColumn, $destinationMIDsToWhitelist)
                    ->exists();
    }

    public function getBankAccountsFromAccountNumberAndType($payeeAccount, $type, $withTrashed = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::ACCOUNT_NUMBER, $payeeAccount)
                      ->where(Entity::TYPE, $type);

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }

        return $query->first();
    }

    public function findbyPublicIdAndMerchantAlsoWithTrash(
        string $id,
        Merchant\Entity $merchant,
        $withTrashed = true): PublicEntity
    {

        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        $query = $this->newQuery()
                      ->where(Entity::ID, $id)
                      ->where(Entity::MERCHANT_ID, $merchant->getId());

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }

        $entity = $query->first();

        if (method_exists($entity, 'merchant') === true)
        {
            $entity->merchant()->associate($merchant);
        }

        return $entity;
    }

    /**
     * Takes a join of bank_accounts table with virtual accounts table on the `bank_accounts`.`entity_id` column and then
     * another join with balance table on `balance`.id column and then fetches the bank account numbers of active virtual
     * accounts linked to a merchant id where the account_type is shared and the balance type is banking
     *
     * @param array $merchantIds
     *
     * @return
     */
    public function getBankAccountAccountNumbersOfActiveVirtualAccountsFromMerchantIds(array $merchantIds)
    {
        $virtualAccountBankAccountIdCol = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID);
        $virtualAccountBalanceIdColumn  = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::BALANCE_ID);

        $bankAccountIdColumn            = $this->repo->bank_account->dbColumn(Entity::ID);
        $bankAccountAccountNumberColumn = $this->repo->bank_account->dbColumn(Entity::ACCOUNT_NUMBER);

        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);
        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceMerchantIdColumn  = $this->repo->balance->dbColumn(Balance\Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($balanceMerchantIdColumn, $bankAccountAccountNumberColumn)
                    ->join(Table::VIRTUAL_ACCOUNT, $virtualAccountBankAccountIdCol, '=', $bankAccountIdColumn)
                    ->join(Table::BALANCE, $virtualAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->whereIn($balanceMerchantIdColumn, $merchantIds)
                    ->where(VirtualAccount\Entity::STATUS, '=', VirtualAccount\Status::ACTIVE)
                    ->where($balanceAccountTypeColumn, '=', 'shared')
                    ->where($balanceTypeColumn, '=', 'banking')
                    ->get();
    }
}
