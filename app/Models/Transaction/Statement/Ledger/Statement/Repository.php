<?php

namespace RZP\Models\Transaction\Statement\Ledger\Statement;

use Db;
use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Constants\Mode;
use RZP\Models\Reversal;
use RZP\Models\External;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Metric;
use RZP\Models\FundAccount;
use RZP\Base\ConnectionType;
use RZP\Models\BankTransfer;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Transaction\Statement;
use RZP\Models\FundAccount\Validation;
use Illuminate\Database\Query\JoinClause;
use RZP\Models\Transaction\Metric as TxnMetric;
use RZP\Models\Transaction\Statement\Ledger\Journal as Journal;
use RZP\Models\Transaction\Statement\Ledger\LedgerEntry as LedgerEntry;
use RZP\Models\Transaction\Statement\Ledger\AccountDetail as AccountDetail;


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
    protected $entity = 'ledger_statement';

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
     * In GET and LIST for only source of type payout laze loads following nested relations.
     * This array is used when a transaction is fetched via ledger flow.
     * @var array
     */
    protected $expandsForTypePayoutForLedger = [
        'fundAccount.contact',
        'fundAccount.account',
        'reversal',
    ];

    /**
     * In GET and LIST for only source of type fund account validation laze loads following nested relations.
     * @var array
     */
    protected $expandsForTypeFAV = [
        'source.fundAccount.contact',
        'source.fundAccount.account',
    ];

    const MERCHANT_ID_LEDGER_ACCOUNT_ID_CACHE_PREFIX   = 'merchant_account_id_cache_';

    /**
     * {@inheritDoc}
     * This method overrides the fetch method of RepositoryFetch class, params should match the signature of the parent
     * method.
     * @throws \Throwable
     */
    public function fetch(array $input,
                          string $merchantId = null,
                          string $connectionType = null): PublicCollection
    {
        try
        {
            $startTimeMs = round(microtime(true) * 1000);

            $statements = parent::fetch($input, $merchantId, $connectionType);

            $endTimeMs = round(microtime(true) * 1000);

            $totalFetchTime = $endTimeMs - $startTimeMs;

            $this->trace->info(TraceCode::QUERY_TIME_FOR_LEDGER_TRANSACTION_API , [
                'duration_ms'    => $totalFetchTime,
                'merchantId'     => $merchantId,
            ]);

            // After fetching settlement collection, we lazy load source relations for payout.
            $statements->where(Entity::TRANSACTOR_TYPE, E::PAYOUT)->load($this->expandsForTypePayout);

            // After fetching settlement collection, we lazy load source relations for Fund account validation.
            $statements->where(Entity::TRANSACTOR_TYPE, E::FUND_ACCOUNT_VALIDATION)->load($this->expandsForTypeFAV);

            return $statements;
        }
        catch (\Throwable $ex)
        {

            $dimensions = [];

            // Adding internal app name in labels
            $dimensions[Metric::LABEL_RZP_INTERNAL_APP_NAME] = app('request.ctx')->getInternalAppName() ?? Metric::LABEL_NONE_VALUE;

            // Increasing error counter
            $this->trace->count(TxnMetric::TRANSACTION_VA_REQUEST_ERROR_COUNT, $dimensions);

            $this->trace->error(TraceCode::TRANSACTION_VA_REQUEST_ERROR_RESPONSE, [
                "merchant_id"   => $merchantId,
                "exception"     => $ex->getMessage(),
            ]);

            throw $ex;
        }
    }

    /**
     * In Fetch merchant_id can also be injected from code.
     * Method will add merchant id in the query even if it
     * is not part of input.
     *
     * @param BuilderEx $query
     * @param string    $merchantId
     */
    protected function addCommonQueryParamMerchantId($query, $merchantId)
    {
       // If experiment active, we fetch ledger merchant account from cache and use here
       if ($this->ledgerTidbMerchantAccountIDCacheExperiment($merchantId) === true)
        {
            $this->addCommonQueryParamMerchantIdExperiment($query, $merchantId);
            return;
        }

        if ($merchantId !== null)
        {
            $query = $query->merchantId($merchantId);

            $accountDetailTable = $this->repo->account_detail->getTableName();

            $accountNameColumn = $this->repo->account_detail->dbColumn(AccountDetail\Entity::ACCOUNT_NAME);

            $query->select($this->getTableName() . '.*');

            $query->leftJoin(
                $accountDetailTable,
                function(JoinClause $join)
                {
                    $accountIdColumn            = $this->repo->account_detail->dbColumn(AccountDetail\Entity::ACCOUNT_ID);
                    $ledgerEntryAccountIdColumn = $this->dbColumn(LedgerEntry\Entity::ACCOUNT_ID);

                    $join->on($accountIdColumn, $ledgerEntryAccountIdColumn);
                });

            $query->where($accountNameColumn, 'Merchant Balance Account - ' . $merchantId);
        }

        //
        // We need to check whether merchant id is required or not
        // to perform the query. This is important because when
        // merchant is making a query, it needs to be enforced
        // and should not be missing by mistake.
        //
        if ($this->isMerchantIdRequiredForFetch())
        {
            if ($merchantId === null)
            {
                throw new InvalidArgumentException('Merchant Id is required for fetch query');
            }
        }
    }

    /**
     * In Fetch merchant_id can also be injected from code.
     * Method will add merchant id in the query even if it
     * is not part of input.
     *
     *  select `prod_pg_ledger_live`.`ledger_entries`.* from `prod_pg_ledger_live`.`ledger_entries`
     *  where `prod_pg_ledger_live`.`ledger_entries`.`account_id` = ?
     *  order by `prod_pg_ledger_live`.`ledger_entries`.`journal_id` desc limit 5 offset 0
     *
     * @param BuilderEx $query
     * @param string    $merchantId
     */
    protected function addCommonQueryParamMerchantIdExperiment($query, $merchantId)
    {
        if ($merchantId !== null)
        {
            $ledgerMerchantBalanceAccountID = $this->getMerchantBalanceAccountIDFromLedger($merchantId);

            $accountIDColumn = $this->dbColumn(LedgerEntry\Entity::ACCOUNT_ID);

            $query->select($this->getTableName() . '.*');

            $query->where($accountIDColumn, $ledgerMerchantBalanceAccountID);
        }

        //
        // We need to check whether merchant id is required or not
        // to perform the query. This is important because when
        // merchant is making a query, it needs to be enforced
        // and should not be missing by mistake.
        //
        if ($this->isMerchantIdRequiredForFetch())
        {
            if ($merchantId === null)
            {
                throw new InvalidArgumentException('Merchant Id is required for fetch query');
            }
        }
    }

    protected function getMerchantBalanceAccountIDFromLedger($merchantId)
    {
        $startTimeMs = round(microtime(true) * 1000);

        // cache look up for ledger account id
        $ledgerAccountID = $this->app['cache']->get(self::MERCHANT_ID_LEDGER_ACCOUNT_ID_CACHE_PREFIX . $merchantId);
        if (!empty($ledgerAccountID))
        {
            $endTimeMs = round(microtime(true) * 1000);
            $this->trace->info(TraceCode::QUERY_TIME_FOR_LEDGER_ACCOUNT_GET, [
                'is_cache'              => true,
                'account_id'            => $ledgerAccountID,
                'cache_lookup_duration' => $endTimeMs - $startTimeMs,
            ]);
            return $ledgerAccountID;
        }

        // db lookup for ledger account id
        $accountName = 'Merchant Balance Account - ' . $merchantId;
        $ledgerAccountID = $this->repo->account_detail->fetchAccountIDByAccountName($accountName, ConnectionType::RX_DATA_WAREHOUSE_MERCHANT);

        $this->app['cache']->put(self::MERCHANT_ID_LEDGER_ACCOUNT_ID_CACHE_PREFIX . $merchantId, $ledgerAccountID, 60 * 60 * 24 * 3);  // 3 days

        $endTimeMs = round(microtime(true) * 1000);

        $this->trace->info(TraceCode::QUERY_TIME_FOR_LEDGER_ACCOUNT_GET, [
            'is_cache'              => false,
            'account_id'            => $ledgerAccountID,
            'query_duration'        => $endTimeMs - $startTimeMs,
        ]);

        return $ledgerAccountID;
    }

    protected function addQueryOrder($query)
    {
        if ($this->app['basicauth']->isVendorPaymentApp() === true)
        {
            $query->orderBy($this->dbColumn(Entity::JOURNAL_ID), 'asc');

            return;
        }

        $query->orderBy($this->dbColumn(Entity::JOURNAL_ID), 'desc');
    }

    /**
     * This array is used when a transaction is fetched via ledger flow.
     * @var array
     */
    protected $expandsForTypeFAVForLedger = [
        'fundAccount.contact',
        'fundAccount.account',
    ];

    /**
     * Set source field on transaction array depending on the source type.
     * @param string $sourceId
     * @param string $sourceType
     * @param array $transaction
     * @param Merchant\Entity $merchant
     * @throws LogicException
     */
    public function setSourceForTransaction(string $sourceId, string $sourceType, array &$transaction, Merchant\Entity $merchant) {
        switch ($sourceType)
        {
            case E::PAYOUT:
                $this->setPayoutAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::BANK_TRANSFER:
                $this->setBankTransferAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::ADJUSTMENT:
                $this->setAdjustmentAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::CREDIT_TRANSFER:
                $this->setCreditTransferAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::REVERSAL:
                $this->setReversalAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::FUND_ACCOUNT_VALIDATION:
                $this->setFundAccountValidationAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            case E::EXTERNAL:
                $this->setExternalAttributesForTxn($sourceId, $transaction, $merchant);
                break;

            default:
                throw new LogicException(SERVICE::SOURCE . ' not implemented at ledger : ' . $sourceType);
        }
    }

    /**
     * select `ledger`.`ledger_entries`.* from `ledger`.`ledger_entries`
     * left join `balance` on `balance`.`merchant_id` = `ledger`.`ledger_entries`.`merchant_id`
     * where `ledger`.`ledger_entries`.`merchant_id` = ? and `balance`.`id` = ?
     * order by `ledger`.`ledger_entries`.`id` desc limit 10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamBalanceId(BuilderEx $query, array $params)
    {
        $balanceId = $params[Entity::BALANCE_ID];
        $balanceIdColumn = $this->repo->balance->dbColumn(Entity::ID);

        $this->joinQueryBalance($query);

        $query->where($balanceIdColumn, '=', $balanceId);
    }

    protected function addQueryParamId($query, $params)
    {
        $id = $params[Entity::ID];

        $idColumn = $this->dbColumn(Entity::JOURNAL_ID);

        Entity::verifyIdAndStripSign($id);

        $query->where($idColumn, $id);
    }

    /**
     * select * from `ledger_entries` where `ledger_entries`.`type` = ?
     * and `ledger_entries`.`amount` > ? order by `ledger_entries`.`id` desc
     * limit 10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamType(BuilderEx $query, array $params)
    {
        $type = $params[Entity::TYPE];
        $typeColumn = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);

        $this->joinQueryJournal($query);

        $query->where($typeColumn, '=', $type);
    }

    /**
     * select * from `ledger_entries` where `ledger_entries`.`type` = ?
     * and `ledger_entries`.`amount` > ? order by `ledger_entries`.`id` desc
     * limit 10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamAction(BuilderEx $query, array $params)
    {
        $action = $params[Entity::ACTION];
        $actionColumn = $this->dbColumn(LedgerEntry\Entity::TYPE);
        $amountColumn = $this->dbColumn(LedgerEntry\Entity::AMOUNT);

        $query->where($actionColumn, '=', $action)
              ->where($amountColumn, '>', 0);
    }

    /**
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
     * where `fund_accounts`.`source_id` = ? and `fund_accounts`.`source_type` = ?
     * order by `ledger_entries`.`id` desc limit 10"
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

    /***
     * select * from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * where `journal`.`transactor_internal_id` = ? and `journal`.`transactor_type` = ?
     * order by `ledger_entries`.`id` desc limit 10"
     * @param BuilderEx $query
     * @param array $params
     */
    protected function addQueryParamPayoutId(BuilderEx $query, array $params)
    {
        $payoutId                  = $params[Entity::PAYOUT_ID];
        $transactionTypeColumn     = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);
        $transactionEntityIdColumn = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_INTERNAL_ID);

        $this->joinQueryJournal($query);

        $query->where($transactionEntityIdColumn, $payoutId);
        $query->where($transactionTypeColumn, E::PAYOUT);
    }

    /***
     * select `ledger_entries`.* from `ledger_entries`
     * left join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * left join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * left join `bank_transfers` on `bank_transfers`.`id` = `journal`.`transactor_internal_id`
     * and `journal`.`transactor_type` = ?
     * left join `external` on `external`.`id` = `journal`.`transactor_internal_id`
     * and `journal`.`transactor_type` = ?
     * left join `reversals` on `reversals`.`id` = `journal`.`transactor_internal_id`
     * and `journal`.`transactor_type` = ?
     * left join `fund_account_validations` on `fund_account_validations`.`id` = `journal`.`transactor_internal_id`
     * and `journal`.`transactor_type` = ?
     * where (`bank_transfers`.`utr` = ? or `payouts`.`utr` = ? or
     * `external`.`utr` = ? or `reversals`.`utr` = ? or `fund_account_validations`.`utr` = ?)
     * order by `ledger_entries`.`id` desc limit 10"
     *
     * @param BuilderEx $query
     * @param array $params
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
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
     * inner join `contacts` on `contacts`.`id` = `fund_accounts`.`source_id`
     * and `fund_accounts`.`source_type` = ?
     * where `contacts`.`name` = ? order by `ledger_entries`.`id` desc limit 10"
     *
     * @param BuilderEx $query
     * @param array $params
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
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
     * inner join `contacts` on `contacts`.`id` = `fund_accounts`.`source_id`
     * and `fund_accounts`.`source_type` = ? where `contacts`.`email` = ?
     * order by `ledger_entries`.`id` desc limit 10"
     *
     * @param BuilderEx $query
     * @param array $params
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
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
     * inner join `contacts` on `contacts`.`id` = `fund_accounts`.`source_id`
     * and `fund_accounts`.`source_type` = ? where `contacts`.`contact` = ?
     * order by `ledger_entries`.`id` desc limit 10"
     *
     * @param BuilderEx $query
     * @param array $params
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
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * inner join `fund_accounts` on `fund_accounts`.`id` = `payouts`.`fund_account_id`
     * inner join `contacts` on `contacts`.`id` = `fund_accounts`.`source_id` and
     * `fund_accounts`.`source_type` = ? where `contacts`.`type` = ?
     * order by `ledger_entries`.`id` desc limit 10
     *
     * @param BuilderEx $query
     * @param array $params
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
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * where `payouts`.`purpose` = ? order by `ledger_entries`.`id` desc limit 10"
     *
     * @param BuilderEx $query
     * @param array $params
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
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * where `payouts`.`fund_account_id` = ?
     * order by `ledger_entries`.`id` desc limit 10
     *
     * @param BuilderEx $query
     * @param array $params
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
     * select `ledger_entries`.* from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * inner join `payouts` on `payouts`.`id` = `journal`.`transactor_internal_id`
     * where `payouts`.`mode` = ? order by `ledger_entries`.`id` desc limit 10
     *
     * @param BuilderEx $query
     * @param array $params
     */
    protected function addQueryParamMode(BuilderEx $query, array $params)
    {
        $mode       = $params[Entity::MODE];
        $modeColumn = $this->repo->payout->dbColumn(Payout\Entity::MODE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayout($query);

        $query->where($modeColumn, $mode);
    }

    /**
     * select * from `ledger_entries`
     * inner join `journal` on `journal`.`id` = `ledger_entries`.`journal_id`
     * where `journal`.`transactor_internal_id` = ? and `journal`.`transactor_type` = ?
     * order by `ledger_entries`.`id` desc limit 10"
     *
     * @param BuilderEx $query
     * @param array $params
     */
    protected function addQueryParamAdjustmentId(BuilderEx $query, array $params)
    {
        $adjustmentId                  = $params[Entity::ADJUSTMENT_ID];
        $transactionTypeColumn     = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);
        $transactionEntityIdColumn = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_INTERNAL_ID);

        $this->joinQueryJournal($query);

        $query->where($transactionEntityIdColumn, $adjustmentId);
        $query->where($transactionTypeColumn, E::ADJUSTMENT);
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
                $merchantIdColumn            = $this->repo->balance->dbColumn(Entity::MERCHANT_ID);
                $ledgerEntryMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

                $join->on($merchantIdColumn, $ledgerEntryMerchantIdColumn);
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
                $transactionTypeColumn     = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);
                $transactionEntityIdColumn = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_INTERNAL_ID);

                $join->on($externalIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Journal\Type::EXTERNAL);
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
                $transactionTypeColumn     = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);
                $transactionEntityIdColumn = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_INTERNAL_ID);

                $join->on($reversalIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Journal\Type::REVERSAL);
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
                $transactionTypeColumn     = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);
                $transactionEntityIdColumn = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_INTERNAL_ID);

                $join->on($favIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Journal\Type::FUND_ACCOUNT_VALIDATION);
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
                $transactionTypeColumn     = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);
                $transactionEntityIdColumn = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_INTERNAL_ID);

                $join->on($bankTransferIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Journal\Type::BANK_TRANSFER);
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
        $journalTable = $this->repo->journal->getTableName();

        if ($query->hasJoin($payoutTable) === true)
        {
            return;
        }

        $joinType = ($leftJoin === true) ? 'leftJoin' : 'join';

        $query->$joinType(
            $journalTable,
            function(JoinClause $join)
            {
                $journalIdColumn            = $this->repo->journal->dbColumn(Journal\Entity::ID);
                $ledgerEntryJournalIdColumn = $this->dbColumn(Entity::JOURNAL_ID);

                $join->on($journalIdColumn, $ledgerEntryJournalIdColumn);
            });

        $query->$joinType(
            $payoutTable,
            function(JoinClause $join)
            {
                $payoutIdColumn            = $this->repo->payout->dbColumn(Payout\Entity::ID);
                $transactionTypeColumn     = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_TYPE);
                $transactionEntityIdColumn        = $this->repo->journal->dbColumn(Journal\Entity::TRANSACTOR_INTERNAL_ID);

                $join->on($payoutIdColumn, $transactionEntityIdColumn);
                $join->where($transactionTypeColumn, Journal\Type::PAYOUT);
            });
    }

    protected function joinQueryJournal(BuilderEx $query, bool $leftJoin = false)
    {
        $journalTable = $this->repo->journal->getTableName();

        if ($query->hasJoin($journalTable) === true)
        {
            return;
        }

        $joinType = ($leftJoin === true) ? 'leftJoin' : 'join';

        $query->$joinType(
            $journalTable,
            function(JoinClause $join)
            {
                $journalIdColumn            = $this->repo->journal->dbColumn(Journal\Entity::ID);
                $ledgerEntryJournalIdColumn = $this->dbColumn(Entity::JOURNAL_ID);

                $join->on($journalIdColumn, $ledgerEntryJournalIdColumn);
            });
    }

    /**
     * Fetch payout entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setPayoutAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant)
    {
        $payout = $this->repo->payout->fetchPayoutWithExpands(PublicEntity::stripDefaultSign($id), $this->expandsForTypePayoutForLedger);

        // Adding fund_account in extraSourceFields because when doing $payout->toArrayPublic(), "fund_account"
        // key gets removed since it is not present in $visible array. Merging it in $source array later.
        $extraSourceFields = [
            Payout\Entity::FUND_ACCOUNT => $payout->fundAccount->toArrayPublic(),
        ];

        $transaction[Service::SOURCE] = $payout->toArrayPublic();
        $transaction[Service::SOURCE] = array_merge($transaction[Service::SOURCE], $extraSourceFields);

        // Calling statement entity function to set public attributes for payout entity.
        $statement = new Statement\Entity();
        $statement->setPublicSourceAttributeForPayout($transaction);
    }

    /**
     * Fetch bank_transfer entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setBankTransferAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant)
    {
        $bankTransfer = $this->repo->bank_transfer->findByPublicIdAndMerchant($id, $merchant);
        $transaction[Service::SOURCE] = $bankTransfer->toArrayPublic();

        // Calling statement entity function to set public attributes for bank_transfer entity.
        $statement = new Statement\Entity();

        // Initializing source since inside setPublicSourceAttributeForBankTransfer, bank_transfer us fetched from source
        $statement->setRelation('source', $bankTransfer);
        $statement->setPublicSourceAttributeForBankTransfer($transaction);
    }

    /**
     * Fetch external entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setAdjustmentAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant)
    {
        $adjustment = $this->repo->adjustment->findByPublicIdAndMerchant($id, $merchant);
        $transaction[Service::SOURCE] = $adjustment->toArrayPublic();

        // Calling statement entity function to set public attributes for adjustment entity.
        $statement = new Statement\Entity();
        $statement->setPublicSourceAttributeForAdjustment($transaction);
    }

    /**
     * Fetch credit_transfer entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setCreditTransferAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant)
    {
        $credit_transfer = $this->repo->credit_transfer->findByPublicIdAndMerchant($id, $merchant);
        $transaction[Service::SOURCE] = $credit_transfer->toArrayPublic();

        // Calling statement entity function to set public attributes for credit_transfer entity.
        $statement = new Statement\Entity();
        $statement->setPublicSourceAttributeForCreditTransfer($transaction);
    }

    /**
     * Fetch reversal entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setReversalAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant)
    {
        $reversal = $this->repo->reversal->findByPublicIdAndMerchant($id, $merchant);
        $transaction[Service::SOURCE] = $reversal->toArrayPublic();

        // Since currently no specific function is present in statement entity to return
        // any other fields for reversal entity, that's why returning directly.
    }

    /**
     * Fetch fund_account_validation entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setFundAccountValidationAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant)
    {
        $fav = $this->repo->fund_account_validation->fetchFAVWithExpands(PublicEntity::stripDefaultSign($id), $this->expandsForTypeFAVForLedger);

        // Adding fund_account in extraSourceFields because when doing $fav->toArrayPublic(), "fund_account"
        // key gets removed since it is not present in $visible array. Merging it in $source array later.
        $extraSourceFields = [
            Payout\Entity::FUND_ACCOUNT => $fav->fundAccount->toArrayPublic(),
        ];

        $transaction[Service::SOURCE] = $fav->toArrayPublic();
        $transaction[Service::SOURCE] = array_merge($transaction[Service::SOURCE], $extraSourceFields);

        // Since currently no specific function is present in statement entity to return
        // any other fields for fund_account_validation entity, that's why returning directly.
    }

    /**
     * Fetch external entity using txn id and then set it's attributes on transaction array.
     * @param string $id
     * @param array $transaction
     * @param Merchant\Entity $merchant
     */
    private function setExternalAttributesForTxn(string $id, array &$transaction, Merchant\Entity $merchant)
    {
        $external = $this->repo->external->findByPublicIdAndMerchant($id, $merchant);

        $transaction[Service::SOURCE] = $external->toArrayPublic();

        // Calling statement entity function to set public attributes for adjustment entity.
        $statement = new Statement\Entity();
        $statement->setPublicSourceAttributeForExternal($transaction);
    }

    // Returns true if experiment and env is present for MID
    protected function ledgerTidbMerchantAccountIDCacheExperiment($merchantID): bool
    {
        $variant = $this->app->razorx->getTreatment($merchantID,
            Merchant\RazorxTreatment::LEDGER_TIDB_MERCHANT_ACCOUNT_ID_CACHE,
            $this->app['basicauth']->getMode() ?? Mode::LIVE
        );

        $this->trace->info(TraceCode::LEDGER_TIDB_MERCHANT_ACCOUNT_CACHE_EXPERIMENT, [
            'variant' => $variant,
        ]);

        return (strtolower($variant) === 'on');
    }
}
