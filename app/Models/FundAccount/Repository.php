<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
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

            default:
                $account = null;

                break;
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

    //Enabling duplicate check on contact null only in case of favs

    public function fetchFundAccountOfTypeBankAccountForContact(Merchant\Entity $merchant,
                                                                Contact\Entity $contact = null,
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

        $contactId = ($contact === null) ? null : $contact->getId();

        $bankAccountSourceType = ($contact === null) ? null : E::CONTACT;

        return $this->newQuery()
                    ->select($allFundAccountAttributes)
                    ->join($bankAccountTable, $faAccountIdColumn, '=', $bankAccountIdColumn)
                    ->where($faSourceIdColumn, '=', $contactId)
                    ->where($bankAccountTypeColumn, '=', $bankAccountSourceType)
                    ->where($bankAccountAccountNumberColumn, '=', $bankAccount[BankAccount\Entity::ACCOUNT_NUMBER])
            // TODO: Can remove strtoupper() if collation for ifsc column is made case insensitive
                    ->where($bankAccountIfscCodeColumn, '=', strtoupper($bankAccount[BankAccount\Entity::IFSC]))
                    ->where($bankAccountBeneficiaryName, '=', $bankAccount[BankAccount\Entity::NAME])
                    ->where($bankAccountMerchantIdColumn, '=', $merchant->getId())
                    ->first();
    }

    public function fetchFundAccountOfTypeVpaForContact(Merchant\Entity $merchant,
                                                        Contact\Entity $contact = null,
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

        list($username, $handle) = explode(Vpa\Entity::AROBASE, $vpa[Vpa\Entity::ADDRESS]);

        $contactId = ($contact === null) ? null : $contact->getId();

        $bankAccountSourceType = ($contact === null) ? null : E::CONTACT;

        return $this->newQuery()
                    ->select($allFundAccountAttributes)
                    ->join($vpaTable, $faAccountIdColumn, '=', $vpaIdColumn)
                    ->where($faSourceIdColumn, '=', $contactId)
                    ->where($vpaTypeColumn, '=', $bankAccountSourceType)
                    ->where($vpaUsernameColumn, $username)
                    ->where($vpaHandleColumn, $handle)
                    ->where($vpaMerchantIdColumn, '=', $merchant->getId())
                    ->first();
    }

    public function fetchRzpFeesFundAccount($merchantId, $contactId)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_TYPE, Entity::CONTACT)
                    ->where(Entity::SOURCE_ID, $contactId)
                    ->merchantId($merchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }
}
