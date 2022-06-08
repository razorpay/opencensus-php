<?php

namespace RZP\Models\Transaction\Statement\DirectAccount\Statement;

use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Constants\Es;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Models\Reversal;
use RZP\Models\External;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Constants\Entity as E;
use RZP\Constants\Environment;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Class Repository
 *
 * @package RZP\Models\Transaction\Statement\Ledger\Statement
 */
class Repository extends Base\Repository
{
    /**
     * {@inheritDoc}
     */
    protected $entity = 'direct_account_statement';

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

    protected function addQueryOrder($query)
    {
        if ($this->app['basicauth']->isVendorPaymentApp() === true)
        {
            $query->orderBy($this->dbColumn(Entity::CREATED_AT), 'asc');

            return;
        }

        parent::addQueryOrder($query);
    }

    /**
     * {@inheritDoc}
     *
     * This method overrides the fetch method of RepositoryFetch class, params should match the signature of the parent
     * method.
     */
    public function fetch(array $input,
                          string $merchantId = null,
                          string $connectionType = null): PublicCollection
    {
        $startTimeMs = round(microtime(true) * 1000);

        $statements = parent::fetch($input, $merchantId, $connectionType);

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::QUERY_TIME_FOR_DIRECT_ACCOUNT_TRANSACTION_API , [
            'duration_ms'    => $totalFetchTime,
            'merchantId'     => $merchantId,
        ]);

        // After fetching settlement collection, we lazy load source relations for payout.
        $statements->where(Entity::ENTITY_TYPE, E::PAYOUT)->load($this->expandsForTypePayout);

        return $statements;
    }

    // overriding this method to run the query on transaction_id column instead of id
    protected function runEsFetch(
        array $params,
        string $merchantId = null,
        array $expands,
        string $connectionType = null): PublicCollection
    {
        $startTimeMs = round(microtime(true) * 1000);
        $response = $this->esRepo->buildQueryAndSearch($params, $merchantId);

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        if($queryDuration > 100) {
            $this->trace->info(TraceCode::ES_SEARCH_DURATION, [
                'duration_ms' => $queryDuration,
                'function'    => 'runESSearch',
            ]);
        }

        // Extract results from ES response. If hit has _source get that else just the document id.
        $result = array_map(
            function ($res)
            {
                return $res[ES::_SOURCE] ?? [Common::ID => $res[ES::_ID]];
            },
            $response[ES::HITS][ES::HITS]);

        if (count($result) === 0)
        {
            return new PublicCollection;
        }

        // If callee expects only es data (for auto-complete etc) then hydrate the result into model and return.
        $esHitsOnly = boolval(($params[EsRepository::SEARCH_HITS]) ?? false);

        if ($esHitsOnly)
        {
            return $this->hydrate($result);
        }

        // Else extract the matched ids and return collection by making a MySQL query on found ids.
        $ids = array_column($result, 'id');
        // This is order of ids from es, sorted by _score first then created_at.
        $order = array_flip($ids);

        $query = $this->newQuery();

        if ((is_null($connectionType) === false) and
            ($this->app['env'] !== Environment::TESTING))
        {
            $connection = $this->getConnectionFromType($connectionType);

            $query = $this->newQueryWithConnection($connection);
        }

        $query = $query->with($expands);

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        if (is_array($ids) || $ids instanceof Arrayable)
        {
            $query = $query->whereIn(Entity::TRANSACTION_ID, $ids);
        }
        else if ($ids !== null)
        {
            $ids = (string) $ids;

            $query = $query->where(Entity::TRANSACTION_ID, '=', $ids);
        }

        $entities = $query
            ->get()
            // MySQL gives results in ascending order of id. Sorting again to keep correct ES's order.
            ->sort(
                function (Entity $x, Entity $y) use ($order)
                {
                    return $order[$x->getTransactionId()] - $order[$y->getTransactionId()];
                })
            ->values();

        // If not all the ids from es are found in MySQL just log an error as this should not happen.
        if (count($ids) !== $entities->count())
        {
            $this->trace->critical(TraceCode::ES_MYSQL_RESULTS_MISMATCH, ['ids' => $ids]);
        }

        return $entities;
    }

    protected function addQueryParamId(BuilderEx $query, array &$params)
    {
        $id = $params[Entity::ID];

        if (strpos($id, Transaction\Entity::getSign()) !== false)
        {
            $txnIdColumn = $this->repo->direct_account_statement->dbColumn(Entity::TRANSACTION_ID);

            Transaction\Entity::verifyIdAndSilentlyStripSign($id);

            unset($params[Entity::ID]);

            $params[Entity::TRANSACTION_ID] = $id;

            $query->where($txnIdColumn, $id);

            return;
        }

        $idColumn = $this->repo->direct_account_statement->dbColumn(Entity::ID);

        Entity::verifyIdAndSilentlyStripSign($id);

        $query->where($idColumn, $id);
    }

    protected function addQueryParamBalanceId(BuilderEx $query, array $params)
    {
        $balanceId = $params[Entity::BALANCE_ID];
        $balanceIdColumn = $this->repo->balance->dbColumn(Entity::ID);

        $query->select($this->getTableName() . '.*');

        $this->joinQueryBalance($query);

        $query->where($balanceIdColumn, '=', $balanceId);
    }

    protected function addQueryParamType(BuilderEx $query, array $params)
    {
        $entityType = $params[Entity::TYPE];
        $entityTypeColumn = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_TYPE);

        $query->where($entityTypeColumn, $entityType);
    }

    protected function addQueryParamAction(BuilderEx $query, array $params)
    {
        $action = $params[Entity::ACTION];
        $actionColumn = $this->dbColumn(Entity::TYPE);
        $amountColumn = $this->dbColumn(Entity::AMOUNT);

        $query->where($actionColumn, '=', $action)
              ->where($amountColumn, '>', 0);
    }

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
        $transactionTypeColumn     = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_TYPE);
        $transactionEntityIdColumn = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_ID);

        $query->where($transactionEntityIdColumn, $payoutId);
        $query->where($transactionTypeColumn, E::PAYOUT);
    }

    protected function addQueryParamUtr(BuilderEx $query, array $params)
    {
        $utr                     = $params[Entity::UTR];
        $payoutUtrColumn         = $this->repo->payout->dbColumn(Payout\Entity::UTR);
        $externalUtrColumn       = $this->repo->external->dbColumn(\RZP\Models\External\Entity::UTR);
        $reversalUtrColumn       = $this->repo->reversal->dbColumn(\RZP\Models\Reversal\Entity::UTR);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query, true);
        $this->joinQueryExternal($query);
        $this->joinQueryReversal($query);

        $query->where(function ($query) use (
            $utr,
            $payoutUtrColumn,
            $externalUtrColumn,
            $reversalUtrColumn)
        {
            $query->orWhere($payoutUtrColumn, $utr)
                ->orWhere($externalUtrColumn,$utr)
                ->orWhere($reversalUtrColumn,$utr);
        });
    }

    protected function addQueryParamContactName(BuilderEx $query, array $params)
    {
        $contactName       = $params[Entity::CONTACT_NAME];
        $contactNameColumn = $this->repo->contact->dbColumn(Contact\Entity::NAME);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactNameColumn, $contactName);
    }

    protected function addQueryParamContactEmail(BuilderEx $query, array $params)
    {
        $contactEmail       = $params[Entity::CONTACT_EMAIL];
        $contactEmailColumn = $this->repo->contact->dbColumn(Contact\Entity::EMAIL);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactEmailColumn, $contactEmail);
    }

    protected function addQueryParamContactPhone(BuilderEx $query, array $params)
    {
        $contactPhone       = $params[Entity::CONTACT_PHONE];
        $contactPhoneColumn = $this->repo->contact->dbColumn(Contact\Entity::CONTACT);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactPhoneColumn, $contactPhone);
    }

    protected function addQueryParamContactType(BuilderEx $query, array $params)
    {
        $contactType       = $params[Entity::CONTACT_TYPE];
        $contactTypeColumn = $this->repo->contact->dbColumn(Contact\Entity::TYPE);

        $query->select($this->getTableName(). '.*');
        $this->joinQueryContact($query);

        $query->where($contactTypeColumn, $contactType);
    }

    protected function addQueryParamPayoutPurpose(BuilderEx $query, array $params)
    {
        $payoutPurpose       = $params[Entity::PAYOUT_PURPOSE];
        $payoutPurposeColumn = $this->repo->payout->dbColumn(Payout\Entity::PURPOSE);

        $query->select($this->getTableName(). '.*');
        $this->joinQueryPayout($query);

        $query->where($payoutPurposeColumn, $payoutPurpose);
    }

    protected function addQueryParamFundAccountId(BuilderEx $query, array $params)
    {
        $faId              = $params[Entity::FUND_ACCOUNT_ID];
        $fundAccountColumn = $this->repo->payout->dbColumn(Payout\Entity::FUND_ACCOUNT_ID);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query);

        $query->where($fundAccountColumn, $faId);
    }

    protected function addQueryParamMode(BuilderEx $query, array $params)
    {
        $mode       = $params[Entity::MODE];
        $modeColumn = $this->repo->payout->dbColumn(Payout\Entity::MODE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query);

        $query->where($modeColumn, $mode);
    }

    protected function joinQueryBalance(BuilderEx $query)
    {
        $balanceTable = $this->repo->balance->getTableName();

        if ($query->hasJoin($balanceTable) === true)
        {
            return;
        }

        $query->leftJoin(
            $balanceTable,
            function(JoinClause $join)
            {
                $balanceAccountNumberColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_NUMBER);
                $basAccountNumberColumn = $this->dbColumn(Entity::ACCOUNT_NUMBER);

                $join->on($balanceAccountNumberColumn, $basAccountNumberColumn);
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

    protected function joinQueryExternal(BuilderEx $query)
    {
        $externalTable = $this->repo->external->getTableName();

        if ($query->hasJoin($externalTable) === true)
        {
            return;
        }
        $query->leftJoin(
            $externalTable,
            function(JoinClause $join)
            {
                $externalIdColumn          = $this->repo->external->dbColumn(External\Entity::ID);
                $transactionTypeColumn     = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_TYPE);
                $transactionEntityIdColumn = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_ID);

                $join->on($externalIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, E::EXTERNAL);
            });
    }

    protected function joinQueryReversal(BuilderEx $query)
    {
        $reversalTable = $this->repo->reversal->getTableName();

        if ($query->hasJoin($reversalTable) === true)
        {
            return;
        }

        $query->leftJoin(
            $reversalTable,
            function(JoinClause $join)
            {
                $reversalIdColumn          = $this->repo->reversal->dbColumn(Reversal\Entity::ID);
                $transactionTypeColumn     = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_TYPE);
                $transactionEntityIdColumn = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_ID);

                $join->on($reversalIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, E::REVERSAL);
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

    protected function joinQueryPayout(BuilderEx $query, bool $leftJoin = false)
    {
        $payoutTable = $this->repo->payout->getTableName();

        if ($query->hasJoin($payoutTable) === true)
        {
            return;
        }

        $joinType = ($leftJoin === true) ? 'leftJoin' : 'join';

        $query->$joinType(
            $payoutTable,
            function(JoinClause $join)
            {
                $payoutIdColumn            = $this->repo->payout->dbColumn(Payout\Entity::ID);
                $transactionTypeColumn     = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_TYPE);
                $transactionEntityIdColumn = $this->repo->direct_account_statement->dbColumn(Entity::ENTITY_ID);

                $join->on($payoutIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, E::PAYOUT);
            });
    }
}
