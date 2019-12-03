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

    public function fetchByIdempotentKey(string $idempotentKey,
                                         string $merchantId,
                                         string $batchId)
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

        $bankAccountTable = $this->repo->bank_account->getTableName();

        $bankAccountIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);

        $bankAccountIfscCodeColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::IFSC_CODE);

        $bankAccountTypeColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::TYPE);

        $bankAccountMerchantIdColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::MERCHANT_ID);

        $bankAccountCreatedAtColumn = $this->repo->bank_account->dbColumn(BankAccount\Entity::CREATED_AT);

        return $this->newQuery()
                    ->select($this->getTableName(). '.*')
                    ->join($bankAccountTable, Entity::ACCOUNT_ID, '=', $bankAccountIdColumn)
                    ->where(Entity::SOURCE_ID, '=', $contact->getId())
                    ->where($bankAccountTypeColumn, '=', E::CONTACT)
                    ->where(BankAccount\Entity::ACCOUNT_NUMBER, '=', $bankAccount[BankAccount\Entity::ACCOUNT_NUMBER])
                    ->where($bankAccountIfscCodeColumn, '=', $bankAccount[BankAccount\Entity::IFSC])
                    ->where(BankAccount\Entity::BENEFICIARY_NAME, '=', $bankAccount[BankAccount\Entity::NAME])
                    ->where($bankAccountMerchantIdColumn, '=', $merchant->getId())
                    ->latest($bankAccountCreatedAtColumn)
                    ->first();
    }

    public function fetchFundAccountOfTypeVpaForContact(Merchant\Entity $merchant,
                                                        Contact\Entity $contact,
                                                        array $input)
    {
        $vpa = $input[Type::VPA];

        $vpaTable = $this->repo->vpa->getTableName();

        $vpaIdColumn = $this->repo->vpa->dbColumn(Vpa\Entity::ID);

        $vpaTypeColumn = $this->repo->vpa->dbColumn(Vpa\Entity::ENTITY_TYPE);

        $vpaCreatedAtColumn = $this->repo->vpa->dbColumn(Vpa\Entity::CREATED_AT);

        $vpaUsernameColumn = $this->repo->vpa->dbColumn(Vpa\Entity::USERNAME);

        $vpaHandleColumn = $this->repo->vpa->dbColumn(Vpa\Entity::HANDLE);

        $vpaMerchantIdColumn = $this->repo->vpa->dbColumn(Vpa\Entity::MERCHANT_ID);

        list($username, $handle) = explode(Vpa\Entity::AROBASE, $vpa[Vpa\Entity::ADDRESS]);

        return $this->newQuery()
                    ->select($this->getTableName(). '.*')
                    ->join($vpaTable, Entity::ACCOUNT_ID, '=', $vpaIdColumn)
                    ->where(Entity::SOURCE_ID, '=', $contact->getId())
                    ->where($vpaTypeColumn, '=', E::CONTACT)
                    ->where($vpaUsernameColumn, $username)
                    ->where($vpaHandleColumn, $handle)
                    ->where($vpaMerchantIdColumn, '=', $merchant->getId())
                    ->latest($vpaCreatedAtColumn)
                    ->first();
    }
}
