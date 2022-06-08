<?php

namespace RZP\Models\Transaction\Statement;

use Db;
use Illuminate\Database\Query\JoinClause;

use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\External;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Base\ConnectionType;
use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundAccount\Validation;

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
        else if ($statement->isTypeFundAccountValidation() === true)
        {
            $statement->load($this->expandsForTypeFAV);
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
                          string $connectionType = null): PublicCollection
    {
        $this->setBaseQueryIfApplicable($merchantId);

        $startTimeMs = round(microtime(true) * 1000);

        $statements = parent::fetch($input, $merchantId, $connectionType);

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::QUERY_TIME_FOR_TRANSACTION_API , [
            'duration_ms'    => $totalFetchTime,
            'merchantId'     => $merchantId,
            'queryparams'    => array_keys($input),
        ]);


        // After fetching settlement collection, we lazy load source relations for payout.
        $statements->where(Entity::TYPE, E::PAYOUT)->load($this->expandsForTypePayout);

        // After fetching settlement collection, we lazy load source relations for Fund account validation.
        $statements->where(Entity::TYPE, E::FUND_ACCOUNT_VALIDATION)->load($this->expandsForTypeFAV);

        return $statements;
    }

    protected function addQueryOrder($query)
    {
        if ($this->app['basicauth']->isVendorPaymentApp() === true)
        {
            $query->orderBy($this->dbColumn(Entity::CREATED_AT), 'asc');

            return;
        }

        parent::addQueryOrder($query);
    }

    public function setBaseQueryAndFetchForBanking(array $input,
                                         string $merchantId = null,
                                         string $connectionType = null,
                                         $balance = null): PublicCollection
    {
        $this->setBaseQueryIfApplicable($merchantId);

        $this->forceIndexForXDashboardDefaultRequests($input ,$balance);

        $startTimeMs = round(microtime(true) * 1000);

        $statements = parent::fetch($input, $merchantId, $connectionType);

        $endTimeMs = round(microtime(true) * 1000);

        $totalFetchTime = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::QUERY_TIME_FOR_TRANSACTION_API_FOR_BANKING , [
            'duration_ms'    => $totalFetchTime,
            'merchantId'     => $merchantId,
            'queryparams'    => array_keys($input),
        ]);


        // After fetching settlement collection, we lazy load source relations for payout.
        $statements->where(Entity::TYPE, E::PAYOUT)->load($this->expandsForTypePayout);

        // After fetching settlement collection, we lazy load source relations for Fund account validation.
        $statements->where(Entity::TYPE, E::FUND_ACCOUNT_VALIDATION)->load($this->expandsForTypeFAV);

        return $statements;
    }

    protected function setBaseQueryIfApplicable(string $merchantId)
    {
        $variant = $this->app->razorx->getTreatment($merchantId,
                                                    Merchant\RazorxTreatment::IGNORE_INDEX_IN_TRANSACTIONS_FETCH,
                                                    $this->app['rzp.mode']);

        if ($variant === 'on')
        {
            $this->baseQuery = $this->setQueryToIgnoreTransactionsCreatedAtIndex();
        }
    }

    protected function setQueryToIgnoreTransactionsCreatedAtIndex($query = null)
    {
        if ($query == null)
        {
            $query = $this->newQueryWithConnection($this->getReportingReplicaConnection());
        }

        $query->from(\DB::raw(Table::TRANSACTION.' IGNORE INDEX (transactions_created_at_index)'));

        return $query;
    }

    protected function forceIndexForXDashboardDefaultRequests($input, $balance)
    {
        if( ($balance != null and $balance->isTypeBanking())
            and (array_key_exists(self::FROM, $input) or $this->checkDefaultFilters($input)) )
        {
            $this->baseQuery = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                ->from(\DB::raw(Table::TRANSACTION.' USE INDEX (transactions_merchant_id_balance_id_created_at_index)'));
        }
    }

    protected function checkDefaultFilters($input)
    {
        if (sizeof($input) === 3 and
            array_key_exists('balance_id', $input) and
            array_key_exists('count', $input) and
            array_key_exists('skip', $input) )
        {
            return true;
        }
        return false;
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
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::BALANCE_ID, $balanceId)
                    ->whereBetween(Entity::CREATED_AT, [$fromDate, $toDate])
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->orderBy(Entity::ID, 'desc')
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
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamAction(BuilderEx $query, array $params)
    {
        $action = $params[Entity::ACTION];
        $actionColumn = $this->dbColumn($action);

        $query->where($actionColumn, '>', 0);
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
     *        LEFT JOIN payouts
     *                ON payouts.id = transactions.entity_id
     *        LEFT JOIN bank_transfers
     *                ON bank_transfers.id = transactions.entity_id
     *        LEFT JOIN external
     *                ON external.id = transactions.entity_id
     *        LEFT JOIN reversals
     *                ON reversals.id = transactions.entity_id
     * WHERE  transactions.merchant_id = '10000000000000'
     *        AND (`bank_transfers`.`utr` = 'Dq87pSmknY5aDy'
     *              or `payouts`.`utr` = 'Dq87pSmknY5aDy'
     *              or `external`.`utr` = 'Dq87pSmknY5aDy'
     *              or `reversals`.`utr` = 'Dq87pSmknY5aDy'
     *            )
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamUtr(BuilderEx $query, array $params)
    {
        $utr                     = $params[Entity::UTR];
        $payoutUtrColumn         = $this->repo->payout->dbColumn(Payout\Entity::UTR);
        $bankTransferUtrColumn   = $this->repo->bank_transfer->dbColumn(\RZP\Models\BankTransfer\Entity::UTR);
        $externalUtrColumn       = $this->repo->external->dbColumn(\RZP\Models\External\Entity::UTR);
        $reversalUtrColumn       = $this->repo->reversal->dbColumn(\RZP\Models\Reversal\Entity::UTR);
        $favUtrColumn            = $this->repo->fund_account_validation->dbColumn(Validation\Entity::UTR);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query, true);
        $this->joinQueryBankTransfer($query);
        $this->joinQueryExternal($query);
        $this->joinQueryReversal($query);
        $this->joinQueryFav($query);

        $query->where(function ($query) use (
            $bankTransferUtrColumn,
            $utr,
            $payoutUtrColumn,
            $externalUtrColumn,
            $reversalUtrColumn,
            $favUtrColumn)
        {
            $query->orWhere($bankTransferUtrColumn, $utr)
                  ->orWhere($payoutUtrColumn, $utr)
                  ->orWhere($externalUtrColumn,$utr)
                  ->orWhere($reversalUtrColumn,$utr)
                  ->orWhere($favUtrColumn,$utr);
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

    protected function addQueryParamAdjustmentId(BuilderEx $query, array $params)
    {
        $adjustmentId                  = $params[Entity::ADJUSTMENT_ID];
        $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);
        $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);

        $query->where($transactionEntityIdColumn, $adjustmentId);
        $query->where($transactionTypeColumn, E::ADJUSTMENT);
    }

    /**
     * Join with payouts table, by default on inner join,
     * if the leftJoin param is true join using leftJoin
     *
     * @param BuilderEx $query
     * @param bool      $leftJoin
     */
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
                $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
                $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);

                $join->on($payoutIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Transaction\Type::PAYOUT);
            });
    }

    protected function joinQueryBankTransfer(BuilderEx $query)
    {
        $bankTransferTable = $this->repo->bank_transfer->getTableName();

        if ($query->hasJoin($bankTransferTable) === true)
        {
            return;
        }

        $query->leftJoin(
            $bankTransferTable,
            function(JoinClause $join)
            {
                $bankTransferIdColumn      = $this->repo->bank_transfer->dbColumn(BankTransfer\Entity::ID);
                $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
                $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);

                $join->on($bankTransferIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Transaction\Type::BANK_TRANSFER);
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
                $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
                $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);

                $join->on($externalIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Transaction\Type::EXTERNAL);
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
                $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
                $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);

                $join->on($reversalIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Transaction\Type::REVERSAL);
            });
    }

    protected function joinQueryFav(BuilderEx $query)
    {
        $FAVTable = $this->repo->fund_account_validation->getTableName();

        if ($query->hasJoin($FAVTable) === true)
        {
            return;
        }

        $query->leftJoin(
            $FAVTable,
            function(JoinClause $join)
            {
                $favIdColumn               = $this->repo->fund_account_validation->dbColumn(Validation\Entity::ID);
                $transactionEntityIdColumn = $this->dbColumn(Entity::ENTITY_ID);
                $transactionTypeColumn     = $this->dbColumn(Entity::TYPE);

                $join->on($favIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Transaction\Type::FUND_ACCOUNT_VALIDATION);
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
