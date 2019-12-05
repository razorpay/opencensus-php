<?php

namespace RZP\Models\Transaction\Statement;

use Illuminate\Database\Query\JoinClause;

use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankingAccountStatement\Entity as BankingAccountStatementEntity;

/**
 * Class Repository
 *
 * @package RZP\Models\Transaction\Statement
 */
class Repository extends Transaction\Repository
{
    /**
     * {@inheritDoc}
     */
    protected $entity = 'statement';

    /**
     * {@inheritDoc}
     */
    protected $expands = [
        Entity::SOURCE,
        Entity::ACCOUNT_BALANCE,
    ];

    /**
     * In GET and LIST for only source of type payout laze loads following nested relations.
     * @var array
     */
    protected $expandsForTypePayout = [
        'source.fundAccount.contact',
        'source.fundAccount.account',
        'source.reversal',
    ];

    /**
     * In GET and LIST for only source of type fund account validation laze loads following nested relations.
     * @var array
     */
    protected $expandsForTypeFAV = [
        'source.fundAccount.contact',
        'source.fundAccount.account',
    ];

    /**
     * {@inheritDoc}
     */
    public function findByPublicIdAndMerchantForBankingBalance(
        string $id,
        Merchant\Entity $merchant,
        array $params = []): Entity
    {
        Entity::verifyIdAndStripSign($id);

        $statement = $this->getQueryForFindWithParams($params)
                          ->merchantId($merchant->getId())
                          ->findOrFailPublic($id);

        if ($statement->isTypePayout() === true)
        {
            $statement->load($this->expandsForTypePayout);
        }

        return $statement;
    }

    /**
     * {@inheritDoc}
     *
     * This method overrides the fetch method of RepositoryFetch class, params should match the signature of the parent
     * method.
     */
    public function fetch(array $input,
                          string $merchantId = null,
                          bool $useSlave = false,
                          bool $useMasterReplica = false): PublicCollection
    {
        $statements = parent::fetch($input, $merchantId, $useSlave, $useMasterReplica);

        // After fetching settlement collection, we lazy load source relations for payout.
        $statements->where(Entity::TYPE, E::PAYOUT)->load($this->expandsForTypePayout);

        // After fetching settlement collection, we lazy load source relations for Fund account validation.
        $statements->where(Entity::TYPE, E::FUND_ACCOUNT_VALIDATION)->load($this->expandsForTypeFAV);

        return $statements;
    }

    /**
     * TODO : https://razorpay.atlassian.net/browse/RX-536
     * @param $merchantId
     * @param $balanceId
     * @param $fromDate
     * @param $toDate
     * @return mixed
     */
    public function getStatementsInRange($merchantId, $balanceId, $fromDate, $toDate)
    {
        $basRepo = $this->repo->banking_account_statement;

        $basTableTransactionColumn = $basRepo->dbColumn(BankingAccountStatementEntity::TRANSACTION_ID);

        $createdAtColumn = $this->dbColumn(Entity::CREATED_AT);

        $idColumn = $this->dbColumn(Entity::ID);

        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);

        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where($balanceIdColumn, $balanceId)
                    ->whereBetween($createdAtColumn, [$fromDate, $toDate])
                    ->orderBy($createdAtColumn, 'desc')
                    ->orderBy($idColumn, 'desc')
                    ->with('bankingAccountStatement')
                    ->get();
    }

    protected function addQueryParamId($query, $params)
    {
        $id = $params[Entity::ID];

        $idColumn = $this->dbColumn(Entity::ID);

        Entity::verifyIdAndStripSign($id);

        $query->where($idColumn, $id);
    }

    /**
     * SELECT *
     * FROM transactions
     * WHERE debit != 0
     *    OR (credit = 0 AND debit = 0)
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamAction(BuilderEx $query, array $params)
    {
        $action = $params[Entity::ACTION];
        $actionColumn = $this->dbColumn($action);

        if ($action === Entity::DEBIT)
        {
            $oppositeActionColumn = $this->dbColumn(Entity::CREDIT);
        }
        else
        {
            $oppositeActionColumn = $this->dbColumn(Entity::DEBIT);
        }

        $query->where($actionColumn, '!=', 0)
              ->orWhere(function ($query) use ($actionColumn, $oppositeActionColumn)
              {
                  $query->where($actionColumn, 0)
                        ->where($oppositeActionColumn, 0);
              });
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND fund_accounts.source_id = 'BXV5GAmaJEcGr1'
     *        AND fund_accounts.source_type = 'contact'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
    */
    protected function addQueryParamContactId(BuilderEx $query, array $params)
    {
        $contactId        = $params[Entity::CONTACT_ID];
        $sourceIdColumn   = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
        $sourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryFundAccount($query);

        $query->where($sourceIdColumn, $contactId);
        $query->where($sourceTypeColumn, E::CONTACT);
    }

    protected function addQueryParamPayoutId(BuilderEx $query, array $params)
    {
        $payoutId                  = $params[Entity::PAYOUT_ID];
        $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);
        $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);

        $query->where($transactionEntityIdColumn, $payoutId);
        $query->where($transactionTypeColumn, E::PAYOUT);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     *        INNER JOIN contacts
     *                ON contacts.id = fund_accounts.source_id
     *                   AND fund_accounts.source_type = 'contact'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND contacts.name = 'jitendra kumar ojha'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactName(BuilderEx $query, array $params)
    {
        $contactName       = $params[Entity::CONTACT_NAME];
        $contactNameColumn = $this->repo->contact->dbColumn(Contact\Entity::NAME);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactNameColumn, $contactName);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     *        INNER JOIN contacts
     *                ON contacts.id = fund_accounts.source_id
     *                   AND fund_accounts.source_type = 'contact'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND contacts.email = 'test@razorpay.com'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactEmail(BuilderEx $query, array $params)
    {
        $contactEmail       = $params[Entity::CONTACT_EMAIL];
        $contactEmailColumn = $this->repo->contact->dbColumn(Contact\Entity::EMAIL);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactEmailColumn, $contactEmail);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     *        INNER JOIN contacts
     *                ON contacts.id = fund_accounts.source_id
     *                   AND fund_accounts.source_type = 'contact'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND contacts.contact = '9931864792'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactPhone(BuilderEx $query, array $params)
    {
        $contactPhone       = $params[Entity::CONTACT_PHONE];
        $contactPhoneColumn = $this->repo->contact->dbColumn(Contact\Entity::CONTACT);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactPhoneColumn, $contactPhone);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     *        INNER JOIN contacts
     *                ON contacts.id = fund_accounts.source_id
     *                   AND fund_accounts.source_type = 'contact'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND contacts.type = 'vendor'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactType(BuilderEx $query, array $params)
    {
        $contactType       = $params[Entity::CONTACT_TYPE];
        $contactTypeColumn = $this->repo->contact->dbColumn(Contact\Entity::TYPE);

        $query->select($this->getTableName(). '.*');
        $this->joinQueryContact($query);

        $query->where($contactTypeColumn, $contactType);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND payouts.purpose = 'refund'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamPayoutPurpose(BuilderEx $query, array $params)
    {
        $payoutPurpose       = $params[Entity::PAYOUT_PURPOSE];
        $payoutPurposeColumn = $this->repo->payout->dbColumn(Payout\Entity::PURPOSE);

        $query->select($this->getTableName(). '.*');
        $this->joinQueryPayout($query);

        $query->where($payoutPurposeColumn, $payoutPurpose);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND payouts.fund_account_id = 'BXV5GAmaJEcGr1'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamFundAccountId(BuilderEx $query, array $params)
    {
        $faId              = $params[Entity::FUND_ACCOUNT_ID];
        $fundAccountColumn = $this->repo->payout->dbColumn(Payout\Entity::FUND_ACCOUNT_ID);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query);

        $query->where($fundAccountColumn, $faId);
    }

    /**
     * SELECT transactions.*
     * FROM   transactions
     *        INNER JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *                   AND transactions.type = 'payout'
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND payouts.mode = 'IMPS'
     *        AND transactions.balance_id = 'xbalance000000'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamMode(BuilderEx $query, array $params)
    {
        $mode       = $params[Entity::MODE];
        $modeColumn = $this->repo->payout->dbColumn(Payout\Entity::MODE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query);

        $query->where($modeColumn, $mode);
    }

    protected function joinQueryPayout(BuilderEx $query)
    {
        $payoutTable = $this->repo->payout->getTableName();

        if ($query->hasJoin($payoutTable) === true)
        {
            return;
        }

        $query->join(
            $payoutTable,
            function(JoinClause $join)
            {
                $payoutIdColumn            = $this->repo->payout->dbColumn(Payout\Entity::ID);
                $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
                $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);

                $join->on($payoutIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Transaction\Type::PAYOUT);
            });
    }

    protected function joinQueryFundAccount(BuilderEx $query)
    {
        $faTable = $this->repo->fund_account->getTableName();

        if ($query->hasJoin($faTable) === true)
        {
            return;
        }

        // Must join payout for joining fund_account.
        $this->joinQueryPayout($query);

        $query->join(
            $faTable,
            function(JoinClause $join)
            {
                $faIdColumn       = $this->repo->fund_account->dbColumn(FundAccount\Entity::ID);
                $payoutFaIdColumn = $this->repo->payout->dbColumn(Payout\Entity::FUND_ACCOUNT_ID);

                $join->on($faIdColumn, $payoutFaIdColumn);
            });
    }

    protected function joinQueryContact(BuilderEx $query)
    {
        $contactTable = $this->repo->contact->getTableName();

        if ($query->hasJoin($contactTable) === true)
        {
            return;
        }

        // Must join fund_account for joining contact
        $this->joinQueryFundAccount($query);

        $query->join(
            $contactTable,
            function(JoinClause $join)
            {
                $contactIdColumn    = $this->repo->contact->dbColumn(Contact\Entity::ID);
                $faSourceIdColumn   = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
                $faSourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

                $join->on($contactIdColumn, $faSourceIdColumn);
                $join->where($faSourceTypeColumn, E::CONTACT);
            });
    }
}
