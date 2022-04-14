<?php

namespace RZP\Models\FundAccount;

use DB;

use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\BankAccount;
use RZP\Models\WalletAccount;
use RZP\Constants\Entity as E;

/**
 * Class Repository
 *
 * @package RZP\Models\FundAccount
 */
class Repository extends Base\Repository
{
    protected $entity = 'fund_account';

    protected $expands = [
        Entity::ACCOUNT,
    ];

    /**
     * Get fund account if exists with similar details.
     * @param  array               $input
     * @param  Merchant\Entity     $merchant
     * @param  Contact\Entity|null $contact
     * @return Entity|null
     */
    public function getFundAccountWithSimilarDetails(
        array $input,
        Merchant\Entity $merchant,
        Contact\Entity $contact = null)
    {
        // Finds account (bank account/vpa) against input details.
        switch ($input[Entity::ACCOUNT_TYPE])
        {
            case Type::BANK_ACCOUNT:
                $account = $this->fetchFundAccountOfTypeBankAccountForContact($merchant, $contact, $input);

                break;

            case Type::VPA:
                $account = $this->fetchFundAccountOfTypeVpaForContact($merchant, $contact, $input);

                break;

            case Type::WALLET_ACCOUNT:
                $account = $this->fetchFundAccountOfTypeWalletAccountForContact($merchant, $contact, $input);

                break;

            default:
                $account = null;

                break;
        }

        return $account;
    }

    /**
     * Get fund account if exists with similar details using unique_hash.
     *
     * @param string|null         $uniqueHash
     * @param Contact\Entity|null $contact
     *
     * @return Entity|null
     */
    public function getFundAccountWithSimilarDetailsFromHash(string $uniqueHash = null)
    {
       $account = null;

       if (empty($uniqueHash) === false)
       {
           $allFundAccountAttributes = $this->dbColumn('*');

           $faUniqueHashColumn = $this->dbColumn(Entity::UNIQUE_HASH);

           $faActiveColumn = $this->dbColumn(Entity::ACTIVE);

           $account = $this->newQueryWithConnection($this->getSlaveConnection())
                           ->select($allFundAccountAttributes)
                           ->where($faUniqueHashColumn, '=', $uniqueHash)
                           ->orderBy($faActiveColumn, 'desc')
                           ->orderBy(Entity::CREATED_AT, 'asc')
                           ->first();
       }

        return $account;
    }

    protected function addQueryParamCustomerId($query, $params)
    {
        $query->where(Entity::SOURCE_ID, $params[Entity::CUSTOMER_ID])
            ->where(Entity::SOURCE_TYPE, E::CUSTOMER);
    }

    protected function addQueryParamContactId($query, $params)
    {
        $query->where(Entity::SOURCE_ID, $params[Entity::CONTACT_ID])
            ->where(Entity::SOURCE_TYPE, E::CONTACT);
    }

    public function fetchByIdempotentKey(string $idempotentKey,
                                         string $merchantId,
                                         string $batchId = null)
    {
        return $this->newQuery()
                    ->where(Entity::IDEMPOTENCY_KEY, '=', $idempotentKey)
                    ->where(Entity::BATCH_ID, $batchId)
                    ->merchantId($merchantId)
                    ->first();
    }

    public function fetchFundAccountOfTypeBankAccountForContact(Merchant\Entity $merchant,
                                                                Contact\Entity $contact,
                                                                array $input)
    {
        $bankAccount = $input[Type::BANK_ACCOUNT];

        $allFundAccountAttributes = $this->dbColumn('*');

        $faAccountIdColumn = $this->dbColumn(Entity::ACCOUNT_ID);

        $faSourceIdColumn = $this->dbColumn(Entity::SOURCE_ID);

        $bankAccountTable = $this->repo->bank_account->getTableName();

        $bankAccountIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);

        $bankAccountAccountNumberColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ACCOUNT_NUMBER);

        $bankAccountBeneficiaryName = $this->repo->bank_account->dbColumn(BankAccount\Entity::BENEFICIARY_NAME);

        $bankAccountIfscCodeColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::IFSC_CODE);

        $bankAccountTypeColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::TYPE);

        $bankAccountMerchantIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::MERCHANT_ID);

        $bankAccountCreatedAtColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::CREATED_AT);

        $faActiveColumn = $this->dbColumn(Entity::ACTIVE);

        if ($merchant->isFeatureEnabled(Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA))
        {
            // TODO: Can remove strtoupper() if collation for ifsc column is made case insensitive
            $ifsc = substr(strtoupper($bankAccount[BankAccount\Entity::IFSC]), 0, 4);

            return $this->newQuery()
                        ->select($allFundAccountAttributes)
                        ->join($bankAccountTable, $faAccountIdColumn, '=', $bankAccountIdColumn)
                        ->where($bankAccountTypeColumn, '=', E::CONTACT)
                        ->where($bankAccountAccountNumberColumn, '=', $bankAccount[BankAccount\Entity::ACCOUNT_NUMBER])
                        ->where($bankAccountIfscCodeColumn, 'LIKE', $ifsc . '%')
                        ->where($bankAccountMerchantIdColumn, '=', $merchant->getId())
                        ->orderBy($faActiveColumn, 'desc')
                        ->orderBy(Entity::CREATED_AT, 'asc')
                        ->first();
        }
        else
        {
            return $this->newQuery()
                        ->select($allFundAccountAttributes)
                        ->join($bankAccountTable, $faAccountIdColumn, '=', $bankAccountIdColumn)
                        ->where($faSourceIdColumn, '=', $contact->getId())
                        ->where($bankAccountTypeColumn, '=', E::CONTACT)
                        ->where($bankAccountAccountNumberColumn, '=', $bankAccount[BankAccount\Entity::ACCOUNT_NUMBER])
                // TODO: Can remove strtoupper() if collation for ifsc column is made case insensitive
                        ->where($bankAccountIfscCodeColumn, '=', strtoupper($bankAccount[BankAccount\Entity::IFSC]))
                        ->where($bankAccountBeneficiaryName, '=', $bankAccount[BankAccount\Entity::NAME])
                        ->where($bankAccountMerchantIdColumn, '=', $merchant->getId())
                        ->orderBy($faActiveColumn, 'desc')
                        ->orderBy(Entity::CREATED_AT, 'asc')
                        ->first();
        }
    }

    public function fetchFundAccountOfTypeVpaForContact(Merchant\Entity $merchant,
                                                        Contact\Entity $contact,
                                                        array $input)
    {
        $vpa = $input[Type::VPA];

        $allFundAccountAttributes = $this->dbColumn('*');

        $faAccountIdColumn = $this->dbColumn(Entity::ACCOUNT_ID);

        $faSourceIdColumn = $this->dbColumn(Entity::SOURCE_ID);

        $vpaTable = $this->repo->vpa->getTableName();

        $vpaIdColumn = $this->repo->vpa->dbColumn(Vpa\Entity::ID);

        $vpaTypeColumn = $this->repo->vpa->dbColumn(Vpa\Entity::ENTITY_TYPE);

        $vpaCreatedAtColumn = $this->repo->vpa->dbColumn(Vpa\Entity::CREATED_AT);

        $vpaUsernameColumn = $this->repo->vpa->dbColumn(Vpa\Entity::USERNAME);

        $vpaHandleColumn = $this->repo->vpa->dbColumn(Vpa\Entity::HANDLE);

        $vpaMerchantIdColumn = $this->repo->vpa->dbColumn(Vpa\Entity::MERCHANT_ID);

        $faActiveColumn = $this->dbColumn(Entity::ACTIVE);

        list($username, $handle) = explode(Vpa\Entity::AROBASE, $vpa[Vpa\Entity::ADDRESS]);

        return $this->newQuery()
                    ->select($allFundAccountAttributes)
                    ->join($vpaTable, $faAccountIdColumn, '=', $vpaIdColumn)
                    ->where($faSourceIdColumn, '=', $contact->getId())
                    ->where($vpaTypeColumn, '=', E::CONTACT)
                    ->where($vpaUsernameColumn, $username)
                    ->where($vpaHandleColumn, $handle)
                    ->where($vpaMerchantIdColumn, '=', $merchant->getId())
                    ->orderBy($faActiveColumn, 'desc')
                    ->orderBy(Entity::CREATED_AT, 'asc')
                    ->first();
    }

    /**
     * @param $merchantId
     * @param $contactId
     * @param $ifsc
     *  We have one internal rzp contact with multiple fund accounts with different ifsc/banks
     *  for fee recovery.
     *  This function returns appropriate fund account where fee recovery is to be made
     *
     * @return mixed
     */
    public function fetchRzpFeesFundAccount($merchantId, $contactId, $ifsc)
    {
        $faAccountIdColumn = $this->dbColumn(Entity::ACCOUNT_ID);

        $faColumns = $this->dbColumn('*');

        $faSourceTypeColumn = $this->dbColumn(Entity::SOURCE_TYPE);

        $faSourceIdColumn = $this->dbColumn(Entity::SOURCE_ID);

        $faCreatedAtColumn = $this->dbColumn(Entity::CREATED_AT);

        $bankAccountIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);

        $bankAccountIFSCColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::IFSC_CODE);

        return $this->newQuery()
                    ->select($faColumns)
                    ->join(Table::BANK_ACCOUNT, $bankAccountIdColumn, '=', $faAccountIdColumn)
                    ->where($faSourceTypeColumn, Entity::CONTACT)
                    ->where($faSourceIdColumn, $contactId)
                    ->merchantId($merchantId)
                    ->where($bankAccountIFSCColumn, '=', $ifsc)
                    ->orderBy($faCreatedAtColumn, 'desc')
                    ->first();
    }

    /**
     * Fetch bank accounts with space or line break in beneficiary_name
     *
     * @param $from
     * @param $to
     * @param int $limit
     * @return mixed
     */
    public function fetchBankAccountsHavingSpaceInBeneficiaryName($merchantIds,
                                                                  $from,
                                                                  $to,
                                                                  $limit = 1000)
    {
        $faIdColumn = $this->dbColumn(Entity::ID);

        $faAccountIdColumn = $this->dbColumn(Entity::ACCOUNT_ID);

        $faAccountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $bankAccountTable = $this->repo->bank_account->getTableName();

        $bankAccountIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);

        $bankAccountBeneficiaryName = $this->repo->bank_account->dbColumn(BankAccount\Entity::BENEFICIARY_NAME);

        $bankAccountCreatedAtColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::CREATED_AT);

        $bankAccountMerchantIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($faIdColumn, $faAccountIdColumn)
                    ->join($bankAccountTable, $faAccountIdColumn, '=', $bankAccountIdColumn)
                    ->whereIn($bankAccountMerchantIdColumn, $merchantIds)
                    ->where($faAccountTypeColumn, '=' ,Type::BANK_ACCOUNT)
                    ->where($bankAccountCreatedAtColumn, '>=', $from)
                    ->where($bankAccountCreatedAtColumn, '<=', $to)
                    ->where(
                        DB::raw('CHAR_LENGTH(' . $bankAccountBeneficiaryName . ')'),
                        '>',
                        DB::raw('CHAR_LENGTH(trim(replace(' . $bankAccountBeneficiaryName . ',"\n"," ")))')
                    )
                    ->limit($limit)
                    ->get();
    }

    /**
     * Fetch bank accounts with space or line break in account_ number
     *
     * @param $from
     * @param $to
     * @param int $limit
     * @return mixed
     */
    public function fetchBankAccountsHavingSpaceInAccountNumber($merchantIds,
                                                                $from,
                                                                $to,
                                                                $limit = 1000)
    {
        $faIdColumn = $this->dbColumn(Entity::ID);

        $faAccountIdColumn = $this->dbColumn(Entity::ACCOUNT_ID);

        $faAccountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $bankAccountTable = $this->repo->bank_account->getTableName();

        $bankAccountIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);

        $bankAccountAccountNumberColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ACCOUNT_NUMBER);

        $bankAccountCreatedAtColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::CREATED_AT);

        $bankAccountMerchantIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($faIdColumn, $faAccountIdColumn)
                    ->join($bankAccountTable, $faAccountIdColumn, '=', $bankAccountIdColumn)
                    ->whereIn($bankAccountMerchantIdColumn, $merchantIds)
                    ->where($faAccountTypeColumn, '=' ,Type::BANK_ACCOUNT)
                    ->where($bankAccountCreatedAtColumn, '>=', $from)
                    ->where($bankAccountCreatedAtColumn, '<=', $to)
                    ->where(
                        DB::raw('CHAR_LENGTH(' . $bankAccountAccountNumberColumn . ')'),
                        '>',
                        DB::raw('CHAR_LENGTH(trim(replace(' . $bankAccountAccountNumberColumn . ',"\n","")))')
                    )
                    ->limit($limit)
                    ->get();
    }

    public function fetchFundAccountOfTypeWalletAccountForContact(Merchant\Entity $merchant,
                                                                  Contact\Entity $contact,
                                                                  array $input)
    {
        $walletAccount = $input[Type::WALLET_ACCOUNT];

        $allFundAccountAttributes = $this->dbColumn('*');

        $faAccountIdColumn = $this->dbColumn(Entity::ACCOUNT_ID);

        $faSourceIdColumn = $this->dbColumn(Entity::SOURCE_ID);

        $walletAccountTable = $this->repo->wallet_account->getTableName();

        $walletAccountIdColumn = $this->repo->wallet_account->dbColumn(WalletAccount\Entity::ID);

        $walletAccountTypeColumn = $this->repo->wallet_account->dbColumn(WalletAccount\Entity::ENTITY_TYPE);

        $walletAccountProviderColumn = $this->repo->wallet_account->dbColumn(WalletAccount\Entity::PROVIDER);

        $walletAccountPhoneNoColumn = $this->repo->wallet_account->dbColumn(WalletAccount\Entity::PHONE);

        $walletAccountMerchantIdColumn = $this->repo->wallet_account->dbColumn(WalletAccount\Entity::MERCHANT_ID);

        $faActiveColumn = $this->dbColumn(Entity::ACTIVE);

        return $this->newQuery()
                    ->select($allFundAccountAttributes)
                    ->join($walletAccountTable, $faAccountIdColumn, '=', $walletAccountIdColumn)
                    ->where($faSourceIdColumn, '=', $contact->getId())
                    ->where($walletAccountTypeColumn, '=', E::CONTACT)
                    ->where($walletAccountPhoneNoColumn, '=', $walletAccount[WalletAccount\Entity::PHONE])
                    ->where($walletAccountProviderColumn, '=', $walletAccount[WalletAccount\Entity::PROVIDER])
                    ->where($walletAccountMerchantIdColumn, '=', $merchant->getId())
                    ->orderBy($faActiveColumn, 'desc')
                    ->orderBy(Entity::CREATED_AT, 'asc')
                    ->first();
    }
}
