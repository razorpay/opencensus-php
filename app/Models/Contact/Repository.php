<?php

namespace RZP\Models\Contact;

use Illuminate\Database\Query\JoinClause;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;
use RZP\Models\FundAccount;
use RZP\Models\BankAccount;

/**
 * Class Repository
 *
 * @package RZP\Models\Contact
 */
class Repository extends Base\Repository
{
    protected $entity = 'contact';

    /**
     * Get contact if exists with similar details.
     * @param  array           $input
     * @param  Merchant\Entity $merchant
     * @return Entity|null
     */
    public function getContactWithSimilarDetails(array $input, Merchant\Entity $merchant)
    {
        // In case all of the input parameters exactly match
        // with any existing contact, we return the same contact
        // to the merchant. Name and type are inclusive here.
        return $this->newQuery()
                    ->where(Entity::CONTACT, $input[Entity::CONTACT] ?? null)
                    ->where(Entity::EMAIL, $input[Entity::EMAIL] ?? null)
                    ->where(Entity::REFERENCE_ID, $input[Entity::REFERENCE_ID] ?? null)
                    ->merchantId($merchant->getId())
                    ->where(Entity::TYPE, $input[Entity::TYPE] ?? null)
                    ->where(Entity::NAME, $input[Entity::NAME] ?? null)
                    ->latest(Entity::UPDATED_AT)
                    ->first();
    }

    protected function addQueryParamId($query, $params)
    {
        $id = $params[Entity::ID];

        Entity::verifyIdAndStripSign($id);

        $query->where(Entity::ID, $id);
    }

    /**
     * SELECT contacts.*
     * FROM   contacts
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.source_id = contacts.id
     *                   AND fund_accounts.account_type = 'bank_account'
     *        INNER JOIN bank_accounts
     *                ON bank_accounts.id = fund_accounts.account_id
     * WHERE  contacts.merchant_id = '10000000000000'
     *        AND bank_accounts.account_number = '00000000000001'
     *        AND contacts.deleted_at IS NULL
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    public function addQueryParamAccountNumber(BuilderEx $query, array $params)
    {
        $this->joinQueryBankAccount($query);

        $baAccountNumberAttr = $this->repo->bank_account->dbColumn(BankAccount\Entity::ACCOUNT_NUMBER);
        $baAccountNumber     = $params[Entity::ACCOUNT_NUMBER];

        $query->select($this->getTableName() . '.*');
        $query->where($baAccountNumberAttr, $baAccountNumber);
    }

    /**
     *
     * SELECT contacts.*
     * FROM   contacts
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.source_id = contacts.id
     *                   AND fund_accounts.account_type = 'bank_account'
     * WHERE  contacts.merchant_id = '10000000000000'
     *        AND fund_accounts.id = '10000000000001'
     *        AND contacts.deleted_at IS NULL
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    public function addQueryParamFundAccountId(BuilderEx $query, array $params)
    {
        $this->joinQueryFundAccount($query);

        $faIdColumn = $this->repo->fund_account->dbColumn(Entity::ID);
        $faId       = $params[Entity::FUND_ACCOUNT_ID];

        $query->select($this->getTableName() . '.*');
        $query->where($faIdColumn, $faId);
    }

    protected function joinQueryFundAccount(BuilderEx $query)
    {
        $faTable = $this->repo->fund_account->getTableName();

        if ($query->hasJoin($faTable) === true)
        {
            return;
        }

        $query->join(
            $faTable,
            function(JoinClause $join)
            {
                $contactIdColumn     = $this->dbColumn(Entity::ID);
                $faSourceIdColumn    = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);

                $join->on($faSourceIdColumn, $contactIdColumn);
            });
    }

    protected function joinQueryBankAccount(BuilderEx $query)
    {
        $baTable = $this->repo->bank_account->getTableName();

        if ($query->hasJoin($baTable) === true)
        {
            return;
        }

        // Must join with fund_account table first!
        $this->joinQueryFundAccount($query);

        $query->join(
            $baTable,
            function(JoinClause $join)
            {
                $baIdAttr        = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);
                $faAccountIdAttr = $this->repo->fund_account->dbColumn(FundAccount\Entity::ACCOUNT_ID);

                $join->on($baIdAttr, $faAccountIdAttr);
            });
    }

    public function fetchByIdempotentKey(string $idempotentKey,
                                         string $merchantId,
                                         string $batchId)
    {
        return $this->newQuery()
                    ->where(Entity::IDEMPOTENCY_KEY, $idempotentKey)
                    ->where(Entity::BATCH_ID, $batchId)
                    ->merchantId($merchantId)
                    ->first();
    }
}
