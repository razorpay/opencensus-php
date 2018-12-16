<?php

namespace RZP\Models\Contact;

use Illuminate\Database\Query\JoinClause;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
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

        $faIdAttr = $this->repo->fund_account->dbColumn(Entity::ID);
        $faId     = $params[Entity::FUND_ACCOUNT_ID];

        $query->select($this->getTableName() . '.*');
        $query->where($faIdAttr, $faId);
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
                $contactIdAttr     = $this->dbColumn(Entity::ID);
                $faSourceIdAttr    = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
                $faAccountTypeAttr = $this->repo->fund_account->dbColumn(FundAccount\Entity::ACCOUNT_TYPE);

                $join->on($faSourceIdAttr, $contactIdAttr);
                $join->where($faAccountTypeAttr, FundAccount\Type::BANK_ACCOUNT);
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
}
