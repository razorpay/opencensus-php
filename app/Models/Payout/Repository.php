<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Base\ConnectionType;
use RZP\Models\Feature\Constants;
use Illuminate\Database\Query\JoinClause;

use DB;

use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Feature;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Reversal;
use RZP\Models\Workflow;
use RZP\Models\Admin\Org;
use RZP\Constants\Timezone;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\PayoutSource;
use RZP\Models\Workflow\Step;
use RZP\Constants\Entity as E;
use RZP\Models\Workflow\Action;
use RZP\Models\User\BankingRole;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\PayoutsStatusDetails as PayoutsStatusDetails;
use RZP\Models\PayoutsDetails\Entity as PayoutDetailsEntity;
use RZP\Models\Workflow\Service\StateMap\Entity as WorkflowStateMap;
use RZP\Models\Workflow\Service\EntityMap\Entity as WorkflowEntityMap;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    const  CREATED_AT              = 'created_at';
    const  END_TIMESTAMP           = 'end_timestamp';
    const  ID                      = 'id';
    const  LIMIT                   = 'limit';

    const QUEUED_PAYOUTS_FETCH_LIMIT         = 5000;

    const STUCK_PAYOUTS_FETCH_LIMIT          = 5000;
    const PENDING_PAYOUTS_FETCH_LIMIT        = 5000;
    const BATCH_PAYOUTS_FETCH_LIMIT          = 300;
    const SCHEDULED_PAYOUTS_FETCH_LIMIT      = 5000;
    const PENDING_PAYOUT_COUNT               = 10;
    const PAYOUT_SERVICE_PAYOUTS_FETCH_LIMIT = 2000;

    protected $entity = 'payout';

    /**
     * @param Entity $payout
     * @param array  $options
     */
    public function saveOrFail($payout, array $options = array())
    {
        if (($payout->getIsPayoutService() === true) and
            ($payout->getSavePayoutServicePayoutFlag() === false))
        {
            return;
        }

        $isHighTpsPayout = Core::isHighTpsMerchant($payout);

        ($isHighTpsPayout === true) ? parent::saveOrFailWithoutEsSync($payout, $options) : parent::saveOrFail($payout, $options);
    }

    public function fetchCreatedPayouts($timestamp, $method)
    {
        return $this->newQuery()
                    ->with('destination')
                    ->status(Status::CREATED)
                    ->where(Entity::METHOD, '=', $method)
                    ->createdAtLessThan($timestamp)
                    ->orderBy(Entity::ID)
                    ->get();
    }

    public function fetchMultiple(array $input, Merchant\Entity $merchant, bool $useMasterConnection = false)
    {
        $this->setBaseQueryIfApplicable($merchant, $input, $useMasterConnection);

        if (array_key_exists(Entity::BALANCE_ID, $input))
        {
            $availableBalances = (new Balance\SubBalanceMap\Core)->getSubBalancesForParentBalance($input[Entity::BALANCE_ID]);

            if (count($availableBalances) > 0)
            {
                array_push($availableBalances, $input[Entity::BALANCE_ID]);

                $this->baseQuery->whereIn(Entity::BALANCE_ID, $availableBalances);

                unset($input[Entity::BALANCE_ID]);
            }
        }

        return parent::fetch($input, $merchant->getId());
    }

    protected function setBaseQueryIfApplicable(Merchant\Entity $merchant, array $input, bool $useMasterConnection)
    {
        //for whatsapp merchant we need to route to new whatsapp db
        if ($merchant->isFeatureEnabled(Constants::MERCHANT_ROUTE_WA_INFRA))
        {
            if ((array_key_exists(Entity::REFERENCE_ID, $input)))
            {
                $this->baseQuery = $this->newQueryWithConnection($this->getWhatsappDatabaseConnection())->useWritePdo();
            }
            else
            {
                $this->baseQuery = $this->newQueryWithConnection($this->getWhatsappSlaveConnection());
            }
        }

        else if (($useMasterConnection === true) &&
            (array_key_exists(Entity::REFERENCE_ID, $input)))
        {
            $mode = $this->app['rzp.mode'];
            $this->baseQuery = $this->newQueryWithConnection($mode)->useWritePdo();
        }
        else
        {
            $this->baseQuery = $this->newQueryWithConnection($this->getSlaveConnection());
        }
    }

    public function fetchReversedPayouts(array $ids)
    {
        return $this->newQuery()
                    ->with(['destination', 'fundAccount.account'])
                    ->whereIn(Entity::ID, $ids)
                    ->status(Status::REVERSED)
                    ->get();
    }

    public function fetchPayoutWithExpands(string $id, array $expands)
    {
        $payout = $this->newQuery()
                       ->with($expands)
                       ->where(Entity::ID, $id)
                       ->first();

        if (empty($payout) === true)
        {
            $payout = (new DualWrite\Payout())->getAPIPayoutFromPayoutService($id, true);
        }

        return $payout;
    }

    public function fetchFromUtr($utr, $amount, $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, $balanceId)
                    ->where(Entity::AMOUNT, $amount)
                    ->where(Entity::UTR, $utr)
                    ->get();
    }

    public function fetchFromReturnUtr($utr, $amount, $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, $balanceId)
                    ->where(Entity::AMOUNT, $amount)
                    ->where(Entity::RETURN_UTR, $utr)
                    ->get();
    }

    /**
     * @param $id
     * @return string
     */
    public function findBalanceIdById(string $id)
    {
        $balanceId = $this->repo->payout->dbColumn(Payout\Entity::BALANCE_ID);

        $result = $this->newQuery()
                       ->select($balanceId)
                       ->where(Entity::ID, '=', $id)
                       ->get();

        if (empty($result) === false)
            return $result->first()[Payout\Entity::BALANCE_ID];

        return '';
    }

    /**
     * @param $id
     * @return string
     */
    public function findChannelById(string $id)
    {
        $channel = $this->repo->payout->dbColumn(Payout\Entity::CHANNEL);

        $result = $this->newQuery()
                       ->select($channel)
                       ->where(Entity::ID, '=', $id)
                       ->get();

        if (empty($result) === false)
            return $result->first()[Payout\Entity::CHANNEL];

        return '';
    }

    public function fetchFromCmsRefNumber($cmsRefNumber, $amount, $balanceId)
    {
        $ftaTable = $this->repo->fund_transfer_attempt->getTableName();

        $ftaSourceIdColumn = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);

        $ftaCmsRefNumColumn = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::CMS_REF_NO);

        $payoutsIdColumn = $this->repo->payout->dbColumn(Entity::ID);

        $payoutsBalanceColumn = $this->repo->payout->dbColumn(Entity::BALANCE_ID);

        $payoutModeColumn = $this->repo->payout->dbColumn(Entity::MODE);

        $payoutsAmountColumn = $this->repo->payout->dbColumn(Entity::AMOUNT);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($ftaCmsRefNumColumn, $cmsRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->whereNotIn($payoutModeColumn, [Mode::IFT])
                    ->get();
    }

    public function fetchPayoutsFromCmsRefNumberinTimeRange($cmsRefNumber,
                                                 $txnDateTime,
                                                 $txnDateTimeBefore,
                                                 $amount,
                                                 $balanceId)
    {
        $ftaTable           = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn  = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
        $ftaCmsRefNumColumn = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::CMS_REF_NO);

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutModeColumn           = $this->repo->payout->dbColumn(Entity::MODE);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutInitiatedAtColumn    = $this->repo->payout->dbColumn(Payout\Entity::INITIATED_AT);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($ftaCmsRefNumColumn, $cmsRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->whereNotIn($payoutModeColumn, [Mode::IFT])
                    ->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime])
                    ->get();
    }

    public function fetchUnlinkedPayoutsFromCmsRefNumber($cmsRefNumber, $amount, $balanceId)
    {
        $ftaTable           = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn  = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
        $ftaCmsRefNumColumn = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::CMS_REF_NO);

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutModeColumn           = $this->repo->payout->dbColumn(Entity::MODE);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutsTransactionIdColumn = $this->repo->payout->dbColumn(Entity::TRANSACTION_ID);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($ftaCmsRefNumColumn, $cmsRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->whereNull($payoutsTransactionIdColumn)
                    ->whereNotIn($payoutModeColumn, [Mode::IFT])
                    ->get();
    }

    public function fetchPayoutsFromCmsRefNumberWithinTimeRangeForIFT(
        $cmsRefNumber,
        $txnDateTime,
        $txnDateTimeBefore,
        $amount,
        $balanceId)
    {
        $ftaTable           = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn  = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
        $ftaCmsRefNumColumn = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::CMS_REF_NO);
        $ftaModeColumn      = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::MODE);

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutInitiatedAtColumn    = $this->repo->payout->dbColumn(Payout\Entity::INITIATED_AT);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($ftaCmsRefNumColumn, $cmsRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->where($ftaModeColumn, Mode::IFT)
                    ->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime])
                    ->get();
    }

    public function fetchPayoutsFromGatewayRefNumber(
        $gatewayRefNumber,
        $txnDateTime,
        $txnDateTimeBefore,
        $amount,
        $balanceId,
        $mode = null,
        $isGatewayRefNoCaseSensitive = false)
    {
        $ftaTable           = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn  = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
        $ftaModeColumn      = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::MODE);

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutInitiatedAtColumn    = $this->repo->payout->dbColumn(Payout\Entity::INITIATED_AT);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);

        $payoutAttrs = $this->dbColumn('*');

        $query =  $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($payoutsAmountColumn, $amount);

        if ($isGatewayRefNoCaseSensitive === true)
        {
            $query->whereRaw('UPPER(`fund_transfer_attempts`.`gateway_ref_no`) = ?', strtoupper($gatewayRefNumber));
        }
        else
        {
            $query->whereRaw('`fund_transfer_attempts`.`gateway_ref_no` = ?', $gatewayRefNumber);
        }

        if ($mode !== null)
        {
            $query->where($ftaModeColumn, $mode);
        }

        if (($txnDateTime !== null) and
            ($txnDateTimeBefore !== null))
        {
            $query->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime]);
        }

        return $query->get();
    }

    public function fetchUnlinkedPayoutsFromCmsRefNumberWithinTimeRangeForIFT(
        $cmsRefNumber,
        $txnDateTime,
        $txnDateTimeBefore,
        $amount,
        $balanceId)
    {
        $ftaTable           = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn  = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
        $ftaCmsRefNumColumn = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::CMS_REF_NO);
        $ftaModeColumn      = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::MODE);

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutInitiatedAtColumn    = $this->repo->payout->dbColumn(Payout\Entity::INITIATED_AT);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutsTransactionIdColumn = $this->repo->payout->dbColumn(Entity::TRANSACTION_ID);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($ftaCmsRefNumColumn, $cmsRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->where($ftaModeColumn, Mode::IFT)
                    ->whereNull($payoutsTransactionIdColumn)
                    ->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime])
                    ->get();
    }

    public function fetchUnlinkedPayoutsFromGatewayRefNumber(
        $gatewayRefNumber,
        $txnDateTime,
        $txnDateTimeBefore,
        $amount,
        $balanceId,
        $mode = null,
        $isGatewayRefNoCaseSensitive = false)
    {
        $ftaTable               = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn      = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
        $ftaModeColumn          = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::MODE);

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutInitiatedAtColumn    = $this->repo->payout->dbColumn(Payout\Entity::INITIATED_AT);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutsTransactionIdColumn = $this->repo->payout->dbColumn(Entity::TRANSACTION_ID);

        $payoutAttrs = $this->dbColumn('*');

        $query = $this->newQuery()
                      ->select($payoutAttrs)
                      ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                      ->where($payoutsBalanceColumn, $balanceId)
                      ->where($payoutsAmountColumn, $amount)
                      ->whereNull($payoutsTransactionIdColumn);

        if ($isGatewayRefNoCaseSensitive === true)
        {
            $query->whereRaw('UPPER(`fund_transfer_attempts`.`gateway_ref_no`) = ?', strtoupper($gatewayRefNumber));
        }
        else
        {
            $query->whereRaw('`fund_transfer_attempts`.`gateway_ref_no` = ?', $gatewayRefNumber);
        }

        if ($mode !== null)
        {
            $query->where($ftaModeColumn, $mode);
        }

        if (($txnDateTime !== null) and
            ($txnDateTimeBefore !== null))
        {
            $query->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime]);
        }

        return $query->get();
    }

    public function fetchQueuedPayouts(array $merchantIdsWhitelist = [],
                                       array $merchantIdsBlacklist = [],
                                       string $balanceType = Balance\Type::BANKING,
                                       string $purpose = null)
    {
        // select(payouts.*) because if we don't restrict to payouts table columns,
        // collection_item->balance will return the balance field from joined table
        // as opposed to the expected eager-loaded balance entity
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($this->getTableName() . ".*")
                      ->with(['balance', 'merchant', 'merchant.org'])
                      ->status(Status::QUEUED);

        $merchantIdColumn = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);
        $purposeColumn = $this->repo->payout->dbColumn(Entity::PURPOSE);

        if (empty ($merchantIdsWhitelist) === false)
        {
            $query->whereIn($merchantIdColumn, $merchantIdsWhitelist);
        }

        if (empty($merchantIdsBlacklist) === false)
        {
            $query->whereNotIn($merchantIdColumn, $merchantIdsBlacklist);
        }

        if ($purpose !== null)
        {
            $query->where($purposeColumn, '=', $purpose);
        }

        $this->joinQueryBalance($query);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $query->where($balanceTypeColumn, '=', $balanceType);

        return $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
                     ->get();
    }

    public function fetchQueuedAndOnHoldPayouts(string $merchantId,
                                                string $balanceType = Balance\Type::BANKING)
    {
        // select(payouts.*) because if we don't restrict to payouts table columns,
        // collection_item->balance will return the balance field from joined table
        // as opposed to the expected eager-loaded balance entity

        $statusColumn     = $this->repo->payout->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);
        $createdAtColumn  = $this->repo->payout->dbColumn(Entity::CREATED_AT);

        $afterDate = Carbon::now(Timezone::IST)->startOfDay()->subMonths(3)->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->with(['balance', 'merchant'])
            ->select($this->getTableName() . ".*")
            ->where($createdAtColumn, '>=', $afterDate)
            ->where($merchantIdColumn, '=', $merchantId)
            ->wherein($statusColumn, [Status::QUEUED, Status::ON_HOLD]);

        $this->joinQueryBalance($query);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $query->where($balanceTypeColumn, '=', $balanceType);

        return $query->get();
    }

    public function fetchOptimisedQueuedAndOnHoldPayouts(string $merchantId,
                                                         string $balanceType = Balance\Type::BANKING)
    {
        $statusColumn                = $this->repo->payout->dbColumn(Entity::STATUS);
        $merchantIdColumn            = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);
        $queuedReasonColumn          = $this->repo->payout->dbColumn(Entity::QUEUED_REASON);
        $balanceIdPayoutsTableColumn = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $createdAtPayoutsTableColumn = $this->repo->payout->dbColumn(Entity::CREATED_AT);
        $balanceTypeColumn           = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);
        $balanceIdColumn             = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ID);
        $balanceColumn               = $this->repo->balance->dbColumn(Merchant\Balance\Entity::BALANCE);
        $balanceTable                = $this->repo->balance->getTableName();

        $afterDate = Carbon::now(Timezone::IST)->startOfDay()->subMonths(3)->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->join($balanceTable, $balanceIdPayoutsTableColumn, '=', $balanceIdColumn)
                      ->selectRaw('SUM(' . Entity::AMOUNT . ') AS amount, COUNT(' . 'payouts.id' . ') AS count, balance_id, queued_reason, balance')
                      ->where($merchantIdColumn, '=', $merchantId)
                      ->where($createdAtPayoutsTableColumn, '>=', $afterDate)
                      ->wherein($statusColumn, [Status::QUEUED, Status::ON_HOLD])
                      ->where($balanceTypeColumn, '=', $balanceType)
                      ->groupBy($balanceIdColumn, $queuedReasonColumn, $balanceColumn);

        return $query->get();
    }

    public function fetchScheduledPayouts(string $merchantId)
    {
        $statusColumn       = $this->repo->payout->dbColumn(Entity::STATUS);
        $scheduledAtColumn  = $this->repo->payout->dbColumn(Entity::SCHEDULED_AT);
        $createdAtColumn    = $this->repo->payout->dbColumn(Entity::CREATED_AT);

        $afterDate = Carbon::now(Timezone::IST)->startOfDay()->subMonths(3)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->with(['balance', 'merchant'])
                    ->whereIn($statusColumn, [Status::SCHEDULED, Status::PENDING])
                    ->where($createdAtColumn, '>=', $afterDate)
                    ->whereNotNull($scheduledAtColumn)
                    ->merchantId($merchantId)
                    ->limit(self::SCHEDULED_PAYOUTS_FETCH_LIMIT)
                    ->get();
    }

    public function checkIfPendingPayoutsExist(string $merchantId,
                                               string $balanceType = Balance\Type::BANKING)
    {
        $statusColumn                = $this->repo->payout->dbColumn(Entity::STATUS);
        $createdAtColumn             = $this->repo->payout->dbColumn(Entity::CREATED_AT);
        $merchantIdColumn            = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);
        $balanceIdPayoutsTableColumn = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $balanceTable                = $this->repo->balance->getTableName();
        $balanceIdColumn             = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ID);
        $balanceTypeColumn           = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $afterDate = Carbon::now(Timezone::IST)->startOfDay()->subMonths(3)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->join($balanceTable, $balanceIdPayoutsTableColumn, '=', $balanceIdColumn)
            ->where($statusColumn, '=',Status::PENDING)
            ->where($createdAtColumn, '>=', $afterDate)
            ->where($merchantIdColumn,'=', $merchantId)
            ->where($balanceTypeColumn, '=', $balanceType)
            ->limit(1)
            ->get();

    }

    /**
     * Fetch Balance Ids for all payouts that have at least one queued payout
     *
     * @return mixed
     */
    public function getBalanceIdsWithAtleastOneQueuedPayout()
    {
        $statusColumn       = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn    = $this->dbColumn(Entity::BALANCE_ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($balanceIdColumn)
                    ->where($statusColumn, '=', Status::QUEUED)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::BALANCE_ID)
                    ->toArray();
    }

    // get list of merchant ids who have done payouts in given time period.
    public function getCAMerchantIdsWithAtleastOnePayout(string $channel, int $startTime, int $endTime, int $limit, $blacklistedMerchantIds = [])
    {
        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);
        $balanceChannelColumn     = $this->repo->balance->dbColumn(Balance\Entity::CHANNEL);

        $payoutInitiatedAtColumn = $this->dbColumn(Entity::INITIATED_AT);
        $payoutsBalanceIdColumn  = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutMerchantIdColumn  = $this->dbColumn(Entity::MERCHANT_ID);

        $query = $this->newQuery()
                        ->select($payoutMerchantIdColumn)
                        ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
                        ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                        ->where($balanceAccountTypeColumn, '=', Balance\AccountType::DIRECT)
                        ->where($balanceChannelColumn, '=', $channel)
                        ->whereBetween($payoutInitiatedAtColumn, [$startTime, $endTime]);

        if (empty($blacklistedMerchantIds) === false)
        {
            $query = $query->whereNotIn($payoutMerchantIdColumn, $blacklistedMerchantIds);
        }

        return $query->distinct()
                        ->limit($limit)
                        ->get()
                        ->pluck(Entity::MERCHANT_ID)
                        ->toArray();
    }

    public function getOnHoldPayoutsWithBeneBankUp(array $beneBanksDownList = [])
    {
        $payoutStatus = $this->dbColumn(Entity::STATUS);

        $isPayoutService = $this->dbColumn(Entity::IS_PAYOUT_SERVICE);

        $payoutIdColumn = $this->dbColumn(Entity::ID);

        $fundAccountId = $this->repo->fund_account->dbColumn(FundAccountEntity::ID);

        $fundAccountIdInPayout = $this->dbColumn(Entity::FUND_ACCOUNT_ID);

        $bankAccountIdInFundAccount = $this->repo->fund_account->dbColumn(FundAccountEntity::ACCOUNT_ID);

        $bankAccountId = $this->repo->bank_account->dbColumn(BankAccountEntity::ID);

        $bankAccountIfscColumn = $this->repo->bank_account->dbColumn(BankAccountEntity::IFSC_CODE);

        $queuedReason  = $this->dbColumn(Entity::QUEUED_REASON);

        $query= $this->newQueryWithConnection($this->getSlaveConnection())
                     ->leftJoin(Table::FUND_ACCOUNT, $fundAccountIdInPayout, '=', $fundAccountId)
                     ->leftJoin(Table::BANK_ACCOUNT, $bankAccountIdInFundAccount, '=', $bankAccountId)
                     ->select($payoutIdColumn)
                     ->where($payoutStatus, '=', Status::ON_HOLD)
                     ->where($queuedReason, '=', QueuedReasons::BENE_BANK_DOWN)
                     ->where($isPayoutService, '=', 0);

            if (empty($beneBanksDownList) === false)
            {
                $beneBanksDown = implode('\',\'',$beneBanksDownList);

                $query->whereRaw('SUBSTRING('. $bankAccountIfscColumn . ',1,4) not in (\''. $beneBanksDown . '\')');
            }

        return $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
                     ->get()
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    public function getPartnerBankHoldPayoutsToProcess(string $rawQuery)
    {
        $payoutStatus = $this->dbColumn(Entity::STATUS);

        $isPayoutService = $this->dbColumn(Entity::IS_PAYOUT_SERVICE);

        $payoutIdColumn = $this->dbColumn(Entity::ID);

        $queuedReason =  $this->dbColumn(Entity::QUEUED_REASON);

        $query= $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($payoutIdColumn)
            ->where($payoutStatus, '=', Status::ON_HOLD)
            ->where($queuedReason, '=', QueuedReasons::GATEWAY_DEGRADED)
            ->where($isPayoutService, '=', 0);

        if (strlen($rawQuery) > 0) {
            $query->whereraw($rawQuery);
        }

        return $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    //fetch payouts in provided status
    public function getPayoutsBeforeTimestampForStatus(string $status, int $beforeDate, array $merchantIdsToExclude, array $targetMerchantIds, int $afterDate = null)
    {
        $payoutStatus = $this->dbColumn(Entity::STATUS);

        $payoutIdColumn = $this->dbColumn(Entity::ID);

        $createdAtColumn = $this->dbColumn(Entity::CREATED_AT);

        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $query= $this->newQueryWithConnection($this->getSlaveConnection())
                     ->select($payoutIdColumn)
                     ->where($payoutStatus, '=', $status)
                     ->where($createdAtColumn, '<', $beforeDate);

        if(empty($merchantIdsToExclude) === false)
        {
            $query->whereNotIn($merchantIdColumn, $merchantIdsToExclude);
        }

        if(empty($targetMerchantIds) === false)
        {
            $query->whereIn($merchantIdColumn, $targetMerchantIds);
        }

        if (empty($afterDate) === false)
        {
            $query->where($createdAtColumn, '>', $afterDate);
        }

        return $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
                     ->get()
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    public function getMerchantIdsWithAtleastOneOnHoldPayout()
    {
        $onholdAtColumn = $this->dbColumn(Entity::ON_HOLD_AT);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $isPayoutService = $this->dbColumn(Entity::IS_PAYOUT_SERVICE);
        $queuedReason = $this->dbColumn(Entity::QUEUED_REASON);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($merchantIdColumn)
                    ->where($statusColumn, '=', Status::ON_HOLD)
                    ->where($queuedReason, '=', QueuedReasons::BENE_BANK_DOWN)
                    ->where($isPayoutService, '=', 0)
                    ->whereNotNull($onholdAtColumn)
                    ->distinct()
                    ->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function getMerchantIdsWithLeastOnePartnerBankOnHoldPayout(string $rawQuery)
    {
        $onholdAtColumn = $this->dbColumn(Entity::ON_HOLD_AT);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $isPayoutService = $this->dbColumn(Entity::IS_PAYOUT_SERVICE);
        $queuedReason = $this->dbColumn(Entity::QUEUED_REASON);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($merchantIdColumn)
            ->where($statusColumn, '=', Status::ON_HOLD)
            ->where($queuedReason, '=', QueuedReasons::GATEWAY_DEGRADED)
            ->where($isPayoutService, '=', 0)
            ->whereNotNull($onholdAtColumn)
            ->distinct();

        if (strlen($rawQuery) > 0) {
            $query->whereraw($rawQuery);
        }

        return $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function getOnHoldPayoutsForMerchantIdForOnHoldAtGreaterThanSla(string $merchantId, int $sla, int $fetchLimit)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $onHoldAtColumn = $this->dbColumn(Entity::ON_HOLD_AT);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $payoutIdColumn = $this->dbColumn(Entity::ID);
        $isPayoutService = $this->dbColumn(Entity::IS_PAYOUT_SERVICE);
        $queuedReason = $this->dbColumn(Entity::QUEUED_REASON);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($payoutIdColumn)
                    ->where($statusColumn, '=', Status::ON_HOLD)
                    ->where($queuedReason, '=', QueuedReasons::BENE_BANK_DOWN)
                    ->where($isPayoutService, '=', 0)
                    ->whereNotNull($onHoldAtColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($onHoldAtColumn, "<=", strtotime(('-' . ($sla * 60) . ' seconds'), $currentTimeStamp))
                    ->limit($fetchLimit)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function getPartnerBankOnHoldPayoutsForMerchantIdSlaBreached(string $merchantId, int $sla, int $fetchLimit,
                                                                        $queuedReason, $rawQuery)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $onHoldAtColumn = $this->dbColumn(Entity::ON_HOLD_AT);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $payoutIdColumn = $this->dbColumn(Entity::ID);
        $isPayoutService = $this->dbColumn(Entity::IS_PAYOUT_SERVICE);
        $queuedReasonColumn = $this->dbColumn(Entity::QUEUED_REASON);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($payoutIdColumn)
            ->where($statusColumn, '=', Status::ON_HOLD)
            ->where($queuedReasonColumn, '=', $queuedReason)
            ->where($isPayoutService, '=', 0)
            ->whereNotNull($onHoldAtColumn)
            ->where($merchantIdColumn, '=', $merchantId)
            ->where($onHoldAtColumn, "<=", strtotime(('-' . ($sla * 60) . ' seconds'), $currentTimeStamp));

        if (strlen($rawQuery) > 0) {
            $query->whereraw($rawQuery);
        }

        return $query->limit($fetchLimit)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function fetchQueuedPayoutsForBalanceId(string $balanceId,
                                                          $offset = 0,
                                                   string $purpose = null)
    {
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $purposeColumn = $this->dbColumn(Entity::PURPOSE);

        $newAsvFlow = (new AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__);

        if($newAsvFlow === true)
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection())
                ->with(['balance'])
                ->where($statusColumn, '=', Status::QUEUED)
                ->where($balanceIdColumn, '=', $balanceId);
        } else
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection())
                ->with(['balance', 'merchant', 'merchant.org'])
                ->where($statusColumn, '=', Status::QUEUED)
                ->where($balanceIdColumn, '=', $balanceId);
        }

        if ($purpose != null)
        {
            $query->where($purposeColumn, '=', $purpose);
        }

        if ($offset !== 0)
        {
            $query->offset($offset);
        }

        $payouts =  $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
                     ->get();


        if($newAsvFlow === true)
        {
            foreach ($payouts as $payout)
            {
                if(empty($payout) === false && $payout->getMerchantId() !== null)
                {
                    $payout->merchant;
                }
            }
        }
        return $payouts;
    }

    public function fetchCountOfQueuedPayoutsForBalance(string $balanceId)
    {
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where($statusColumn, '=', Status::QUEUED)
                    ->where($balanceIdColumn, '=', $balanceId)
                    ->count();
    }

    public function fetchPayoutsWithUtrNotNull($from, $to, $merchantId)
    {
        return $this->newQuery()
                    ->betweenTime($from, $to)
                    ->whereNotNull(Entity::UTR)
                    ->merchantId($merchantId)
                    ->get();
    }

    /**
     * @param User\Entity $user
     * @param Merchant\Entity $merchant
     * @param $userRole
     * @param string $balanceType
     * @return Base\Collection
     * @throws Exception\UserWorkflowNotApplicableException
     * @throws \Exception
     */
    public function fetchPayoutsPendingOnUserRole(User\Entity $user,
                                                  Merchant\Entity $merchant,
                                                  $userRole,
                                                  string $balanceType = Balance\Type::BANKING): Base\Collection
    {
        // select(payouts.*) because if we don't restrict to payouts table columns,
        // collection_item->balance will return the balance field from joined table
        // as opposed to the expected eager-loaded balance entity
        /** @var BuilderEx $query */
        $query = $this->newQuery()
                        ->select($this->getTableName() . ".*");

        $userRoleId = [];

        try
        {
            // If the entity is a user(which implies the product is banking),
            // then the role id for that user for the merchant in context
            // will have to be fetched from the merchant_users table.
            // This is because the role_map table doesn't have any merchant context.
            $userRoleId = (new User\Core())->getUserRoleIdInMerchantForWorkflow($user->getId());
        }
        catch (Exception\UserWorkflowNotApplicableException $exception)
        {
            // If user role is not a workflow role
            $userRoleId = [];
        }

        $this->filterByRoleIds($query, $userRoleId, [$user->getId()]);

        $query->merchantId($merchant->getId());

        // TODO: Update this to handle scale
        // JIRA: https://razorpay.atlassian.net/browse/RX-420
        $query->limit(self::PENDING_PAYOUTS_FETCH_LIMIT);

        $query->with(['balance']);

        $this->joinQueryBalance($query);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $createdAtColumn = $this->repo->payout->dbColumn(Entity::CREATED_AT);

        $afterDate = Carbon::now(Timezone::IST)->startOfDay()->subMonths(3)->getTimestamp();

        $query->where($balanceTypeColumn, '=', $balanceType)
              ->where($createdAtColumn, '>=', $afterDate);

        $payouts = $query->get();

        // Since a merchant can have pending payouts in both old and new workflow system
        // Therefore, we also fetch pending payouts processed via workflow service

        /** @var BuilderEx $query */
        $queryForPendingPayoutsViaWorkflowService = $this->newQuery()
                                                         ->select($this->getTableName() . ".*");

        $queryForPendingPayoutsViaWorkflowService->merchantId($merchant->getId());

        // TODO: Update this to handle scale
        // JIRA: https://razorpay.atlassian.net/browse/RX-420
        $queryForPendingPayoutsViaWorkflowService->limit(self::PENDING_PAYOUTS_FETCH_LIMIT);

        $queryForPendingPayoutsViaWorkflowService->with(['balance']);

        $this->joinQueryBalance($queryForPendingPayoutsViaWorkflowService);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $queryForPendingPayoutsViaWorkflowService->where($balanceTypeColumn, '=', $balanceType);

        $this->addQueryParamPendingOnRolesViaWfs($queryForPendingPayoutsViaWorkflowService, [
            Entity::PENDING_ON_ROLES_VIA_WFS => [$userRole]
        ]);

        $pendingPayoutsViaWorkflowService = $queryForPendingPayoutsViaWorkflowService->get();

        $uniquePayouts = [];
        foreach ($pendingPayoutsViaWorkflowService as $pendingPayout)
        {
            if (in_array($pendingPayout->getId(), $uniquePayouts, true) === false)
            {
                $uniquePayouts[] = $pendingPayout->getId();

                $payouts->add($pendingPayout);
            }
        }

        return $payouts;
    }

    public function updateStatus(Base\PublicCollection $payouts, string $status)
    {
        if ($payouts->count() === 0)
        {
            return 0;
        }

        $IdsToUpdate = $payouts->getIds();

        $updatedCount = $this->newQuery()
                             ->whereIn(Entity::ID, $IdsToUpdate)
                             ->update([
                                    Entity::STATUS  => $status
                                ]);

        $expectedCount = count($IdsToUpdate);

        if ($updatedCount !== $expectedCount)
        {
            throw new Exception\LogicException(
                'Failed to update expected number of payout records.',
                null,
                [
                    'expected' => $expectedCount,
                    'updated'  => $updatedCount,
                ]);
        }

        return $updatedCount;
    }

    protected function addQueryParamId(BuilderEx $query, array $params)
    {
        $id = $params[Entity::ID];

        $idColumn = $this->dbColumn(Entity::ID);

        Entity::verifyIdAndStripSign($id);

        $query->where($idColumn, $id);
    }

    protected function addQueryParamPayoutMode(BuilderEx $query, array $params)
    {
        $payoutMode = $params[Entity::PAYOUT_MODE];

        $modeColumn = $this->dbColumn(Entity::MODE);

        $query->where($modeColumn, $payoutMode);
    }

    public function addQueryParamDestination(BuilderEx $query, array $params)
    {
        $destinationId = $params[Entity::DESTINATION];

        Entity::stripSignWithoutValidation($destinationId);

        $query->where(Entity::DESTINATION_ID, $destinationId);
    }

    public function addQueryParamStatus(BuilderEx $query, array $params)
    {
        $publicStatus = $params[Entity::STATUS];
        $statusColumn = $this->dbColumn(Entity::STATUS);

        $mappedStatuses = Status::getInternalStatusFromPublicStatus($publicStatus);

        $query->whereIn($statusColumn, $mappedStatuses);
    }

    public function addQueryParamReason($query, $params)
    {
        $statusReason       = $params[PayoutsStatusDetails\Entity::REASON];
        $statusReasonColumn = $this->repo->payouts_status_details->dbColumn(PayoutsStatusDetails\Entity::REASON);



        $query->select($this->getTableName(). '.*');
        $this->joinQueryPayoutsStatusDetails($query);

        $query->where($statusReasonColumn,$statusReason);
    }

    protected function joinQueryPayoutsStatusDetails(BuilderEx $query)
    {
        $payoutsStatusDetailsTable = $this->repo->payouts_status_details->getTableName();

        if ($query->hasJoin($payoutsStatusDetailsTable) === true)
        {
            return;
        }

        $query->join(
            $payoutsStatusDetailsTable,
            function(JoinClause $join)
            {
                //id column in payouts status details table
                $payoutsStatusDetailsIdColumn = $this->repo->payouts_status_details->dbColumn(PayoutsStatusDetails\Entity::ID);

                // status details id column in payout table
                $payoutStatusDetailsIdColumn             = $this->dbColumn(Entity::STATUS_DETAILS_ID);

                $join->on($payoutsStatusDetailsIdColumn, $payoutStatusDetailsIdColumn);
            });
    }

    /**
     * calculates the sum of `fee` and `tax` of all the payouts initiated for a merchant in given time frame.
     *
     * select SUM(tax) AS tax,SUM(fees) AS fee
     * from `payouts` where `payouts`.`merchant_id` = ?
     * and `payouts`.`balance_id` = ? and
     * and `payouts`.`fee_type` is null
     * and `payouts`.`initiated_at` between ? and ?"
     *
     * @param string $merchantId
     * @param string $balanceId
     * @param int    $startTime
     * @param int    $endTime
     *
     * @return mixed
     */
    public function fetchFeesAndTaxOfPayoutsForGivenBalanceId(
        string $merchantId,
        string $balanceId,
        int $startTime,
        int $endTime)
    {
        $payoutsBalanceIdColumn   = $this->dbColumn(Entity::BALANCE_ID);
        $payoutsInitiatedAtColumn = $this->dbColumn(Entity::INITIATED_AT);
        $payoutsFeeTypeColumn     = $this->dbColumn(Entity::FEE_TYPE);

        $connectionType = $this->getPaymentFetchReplicaConnection();

        if ($this->isExperimentEnabledForId(self::PAYMENT_FETCH_QUERIES_TIDB_MIGRATION, __FUNCTION__) === true)
        {
            $connectionType = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);
        }

        return $this->newQueryWithConnection($connectionType)
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax,
                         SUM(' . Entity::FEES . ') AS fee')
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNull($payoutsFeeTypeColumn)
                    ->whereBetween($payoutsInitiatedAtColumn, [$startTime, $endTime])
                    ->first();
    }

    /**
     * calculates the sum of `fee` and `tax` of all the payouts initiated and then failed for a merchant in
     * given time frame.
     *
     * Payouts can go to failed state from either created or initiated state.
     * We only want to get fees and tax for payouts which went from initiated to failed
     * i.e where initiated_at is not null.
     *
     * select SUM(tax) AS tax,SUM(fees) AS fee
     * from `payouts` where `payouts`.`merchant_id` = ?
     * and `payouts`.`balance_id` = ?
     * and `payouts`.`initiated_at` is not null
     * and `payouts`.`fee_type` is null
     * and `payouts`.`failed_at` between ? and ?
     * and `payouts`.`status` = failed
     *
     * @param string $merchantId
     * @param string $balanceId
     * @param int    $startTime
     * @param int    $endTime
     *
     * @return mixed
     */
    public function fetchFeesAndTaxForFailedPayoutsForGivenBalanceId(
        string $merchantId,
        string $balanceId,
        int $startTime,
        int $endTime)
    {
        $payoutsBalanceIdColumn   = $this->dbColumn(Entity::BALANCE_ID);
        $payoutsInitiatedAtColumn = $this->dbColumn(Entity::INITIATED_AT);
        $payoutsFailedAtColumn    = $this->dbColumn(Entity::FAILED_AT);
        $payoutsStatusColumn      = $this->dbColumn(Entity::STATUS);
        $payoutsFeeTypeColumn     = $this->dbColumn(Entity::FEE_TYPE);

        $connectionType = $this->getPaymentFetchReplicaConnection();

        if ($this->isExperimentEnabledForId(self::PAYMENT_FETCH_QUERIES_TIDB_MIGRATION, __FUNCTION__) === true)
        {
            $connectionType = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);
        }

        return $this->newQueryWithConnection($connectionType)
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax,
                         SUM(' . Entity::FEES . ') AS fee')
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNotNull($payoutsInitiatedAtColumn)
                    ->whereNull($payoutsFeeTypeColumn)
                    ->whereBetween($payoutsFailedAtColumn, [$startTime, $endTime])
                    ->first();
    }

    /**
     * Returns all payouts that have initiated_at between the two timestamps provided
     *
     * @param string $merchantId
     * @param string $balanceId
     * @param int $start
     * @param int $end
     *
     * @return mixed
     */
    public function fetchFeesAndIdOfPayoutsForGivenBalanceIdForPeriod(
        string $merchantId,
        string $balanceId,
        int $start,
        int $end)
    {
        $payoutsIdColumn            = $this->dbColumn(Entity::ID);
        $payoutsFeesColumn          = $this->dbColumn(Entity::FEES);
        $payoutsFeeTypeColumn       = $this->dbColumn(Entity::FEE_TYPE);
        $payoutsBalanceIdColumn     = $this->dbColumn(Entity::BALANCE_ID);
        $payoutsInitiatedAtColumn   = $this->dbColumn(Entity::INITIATED_AT);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($payoutsIdColumn, $payoutsFeesColumn)
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNotNull($payoutsInitiatedAtColumn)
                    ->whereBetween($payoutsInitiatedAtColumn, [$start, $end])
                    ->where(DB::raw('COALESCE(' . $payoutsFeeTypeColumn. ', "")'), '!=', Transaction\CreditType::REWARD_FEE)
                    ->get();
    }

    /**
     * Returns all payouts that have failed_at between the two timestamps provided and where initiated_at is not null
     *
     * @param string $merchantId
     * @param string $balanceId
     * @param int    $start
     * @param int    $end
     *
     * @return mixed
     */
    public function fetchFeesAndIdOfFailedPayoutsForGivenBalanceIdForPeriod(
        string $merchantId,
        string $balanceId,
        int $start,
        int $end)
    {
        $payoutsIdColumn            = $this->dbColumn(Entity::ID);
        $payoutsFeesColumn          = $this->dbColumn(Entity::FEES);
        $payoutsFeeTypeColumn       = $this->dbColumn(Entity::FEE_TYPE);
        $payoutsFailedAtColumn      = $this->dbColumn(Entity::FAILED_AT);
        $payoutsBalanceIdColumn     = $this->dbColumn(Entity::BALANCE_ID);
        $payoutsInitiatedAtColumn   = $this->dbColumn(Entity::INITIATED_AT);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($payoutsIdColumn, $payoutsFeesColumn)
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNotNull($payoutsInitiatedAtColumn)
                    ->whereBetween($payoutsFailedAtColumn, [$start, $end])
                    ->where(DB::raw('COALESCE(' . $payoutsFeeTypeColumn. ', "")'),  '!=', [Transaction\CreditType::REWARD_FEE])
                    ->get();
    }

    /**
     * SELECT payouts.*
     * FROM   payouts
     *        INNER JOIN fund_accounts
     *                ON fund_accounts.id = payouts.fund_account_id
     * WHERE  payouts.merchant_id = '10000000000000'
     *        AND fund_accounts.source_id = 'BXV5GAmaJEcGr1'
     *        AND fund_accounts.source_type = 'contact'
     * ORDER  BY created_at DESC,
     *           id DESC
     * LIMIT  10
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactId(BuilderEx $query, array $params)
    {
        $contactId          = $params[Entity::CONTACT_ID];
        $faSourceIdColumn   = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
        $faSourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryFundAccount($query);

        $query->where($faSourceIdColumn, $contactId);
        $query->where($faSourceTypeColumn, E::CONTACT);
    }

    /**
     * Refer: addQueryParamContactId()
     *
     * @param BuilderEx $query
     * @param array     $params
     */
    protected function addQueryParamContactType(BuilderEx $query, array $params)
    {
        $contactType       = $params[Entity::CONTACT_TYPE];
        $contactTypeColumn = $this->repo->contact->dbColumn(Contact\Entity::TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryContact($query);

        $query->where($contactTypeColumn, $contactType);
    }


    /**
     * Refer: addQueryParamContactId()
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
     * Refer: addQueryParamContactId()
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

    protected function addQueryParamProduct(BuilderEx $query, array $params)
    {
        $product = $params[Merchant\Entity::PRODUCT];

        $productColumn = $this->repo->balance->dbColumn(Payout\Entity::TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryBalance($query);

        $query->where($productColumn, $product);
    }

    protected function addQueryParamSourceTypeExclude(BuilderEx $query, array $params)
    {
        $sourceType = $params[Entity::SOURCE_TYPE_EXCLUDE];
        $query->select($this->getTableName() . '.*');
        $this->leftJoinQueryPayoutSource($query);

        $query->where(function ($query) use ($sourceType) {
            $sourceTypeColumn = $this->repo->payout_source->dbColumn(PayoutSource\Entity::SOURCE_TYPE);
            $query->whereNotIn($sourceTypeColumn, [$sourceType])
                ->orWhereNull($sourceTypeColumn);
        });
        $query->groupBy(Payout\Entity::ID);
    }

    protected function addQueryParamPayoutIds(BuilderEx $query, array $params)
    {
        $payoutIds = $params[Entity::PAYOUT_IDS];

        Entity::verifyIdAndStripSignMultiple($payoutIds);

        $idColumn = $this->dbColumn(Entity::ID);

        $query->whereIn($idColumn, $payoutIds);
    }

    /**
     * Refer: addQueryParamContactId()
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
     * @param BuilderEx $query
     * @param array $params
     * @throws \Exception
     */
    protected function addQueryParamPendingOnRoles(BuilderEx $query, array $params)
    {
        $pendingOnRoles = $params[Entity::PENDING_ON_ROLES];

        $pendingRoleIds = $this->repo->role->fetchIdsByOrgIdNames(
            Org\Entity::RAZORPAY_ORG_ID,
            BankingRole::getNamesForWorkflowRoles($pendingOnRoles));

        // Adding this additional criteria to filter specificly these user_id's
        // This is especially required when there are more than 1 checker on the same level
        $merchantUsers = $this->repo->merchant_user
            ->findByRolesAndMerchantId($pendingOnRoles, $this->merchant->getId());
        $userIdsToFilter = array_pluck($merchantUsers->toArray(), 'user_id');

        $this->filterByRoleIds($query, $pendingRoleIds->pluck('id')->toArray(), $userIdsToFilter);
    }

    /**
     * @param BuilderEx $query
     * @param array $params
     * @throws \Exception
     */
    protected function addQueryParamPendingOnRolesViaWfs(BuilderEx $query, array $params)
    {
        $query->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_merchant_id_created_at_index)'));

        $pendingOnRoles = $params[Entity::PENDING_ON_ROLES_VIA_WFS];

        $query->select($this->getTableName() . '.*');
        $this->joinQueryWorkflowServiceEntities($query, $pendingOnRoles);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $createdAtColumn = $this->dbColumn(Entity::CREATED_AT);

        $afterDate = Carbon::now(Timezone::IST)->startOfDay()->subMonths(3)->getTimestamp();

        $query->where($statusColumn, Status::PENDING)
              ->where($merchantIdColumn, $this->merchant->getId())
              ->where($createdAtColumn, '>=', $afterDate);
    }

    protected function addQueryParamPendingOnMe(BuilderEx $query, array $params)
    {
        $pendingOnMe = (bool) ($params[Entity::PENDING_ON_ME] ?? false);

        if ($pendingOnMe === false)
        {
            return;
        }

        $userRoleId = [];

        try
        {
            // If the entity is a user(which implies the product is banking),
            // then the role id for that user for the merchant in context
            // will have to be fetched from the merchant_users table.
            // This is because the role_map table doesn't have any merchant context.
            $userRoleId = (new User\Core())->getUserRoleIdInMerchantForWorkflow($this->auth->getUser()->getId());
        }
        catch(Exception\UserWorkflowNotApplicableException $exception)
        {
            // If user role is not a workflow role
            $userRoleId = [];
        }

        $this->filterByRoleIds($query, $userRoleId);
    }

    // TODO: Confirm if this is ever used. Remove if not.
    protected function addQueryParamPendingOnMeViaWfs(BuilderEx $query, array $params)
    {
        $query->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_merchant_id_created_at_index)'));

        $pendingOnMe = (bool) ($params[Entity::PENDING_ON_ME_VIA_WFS] ?? false);

        if ($pendingOnMe === false)
        {
            return;
        }

        $userRoleId = [];

        try
        {
            // If the entity is a user(which implies the product is banking),
            // then the role id for that user for the merchant in context
            // will have to be fetched from the merchant_users table.
            // This is because the role_map table doesn't have any merchant context.
            $userRoleId = (new User\Core())->getUserRoleIdInMerchantForWorkflow($this->auth->getUser()->getId());
        }
        catch(Exception\UserWorkflowNotApplicableException $exception)
        {
            // If user role is not a workflow role
            return;
        }

        $this->joinQueryWorkflowServiceEntities($query, $userRoleId);

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $query->where($statusColumn, Status::PENDING)
              ->where($merchantIdColumn, $this->merchant->getId());
    }

    /**
     * filterPayoutsPendingOnUserViaWFS returns those payout IDs in the payoutIdList that are pending on the user role.
     * select `payouts`.*
     * inner join `workflow_entity_map` on
     *      `payouts`.`id` = `workflow_entity_map`.`entity_id`
     *      and `workflow_entity_map`.`entity_type` = ?
     * inner join `workflow_state_map` on
     *      `workflow_entity_map`.`workflow_id` = `workflow_state_map`.`workflow_id`
     *      and `workflow_state_map`.`status` = ? and `workflow_state_map`.`actor_type_value` in (?)
     * where
     *      `payouts`.`status` = 'pending'
     *      and `payouts`.`merchant_id` = ?
     *      and `workflow_state_map`.`group_name` = (
     *          select max(`wsm`.`group_name`)
     *          from `workflow_state_map` as `wsm`
     *          where `wsm`.`workflow_id` = `workflow_entity_map`.`workflow_id`
     *      )
     *      and `payouts`.`id` in ?
     * @param array $payoutIdList
     */
    public function filterPayoutsPendingOnUserViaWFS(array $payoutIdList)
    {
        $payoutIdColumn = $this->dbColumn(self::ID);
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $workflowGroupNameColumn = $this->repo->workflow_state_map->dbColumn(Workflow\Service\StateMap\Entity::GROUP_NAME);
        $entityMapWorkflowIdColumn = $this->repo->workflow_entity_map->dbColumn(Workflow\Service\StateMap\Entity::WORKFLOW_ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($this->getTableName() . '.*')
                      ->from($this->getTableName());

        $user = $this->auth->getUser();
        if (empty($user) == true)
        {
            return [];
        }

        $this->joinQueryWorkflowServiceEntities($query, [$this->auth->getUserRole()]);

        $query->where($workflowGroupNameColumn,  function($subQuery) use($entityMapWorkflowIdColumn, $query) {
            $subQuery->select(\DB::raw("max(wsm.group_name)"))
                     ->from($this->repo->workflow_state_map->getTableName() . ' as wsm')
                     ->whereColumn('wsm.workflow_id', $entityMapWorkflowIdColumn);
        });

        return $query->where($statusColumn, Status::PENDING)
                     ->where($merchantIdColumn, $this->merchant->getId())
                     ->whereIn($payoutIdColumn, $payoutIdList)
                     ->get()
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    protected function filterByRoleIds(BuilderEx $query, array $roleIds, array $userId = null)
    {
        $permissionId = ''; // Resolve from name

        $query->select($this->getTableName() . '.*');
        $this->joinQueryWorkflowAction($query);

        $statusColumn = $this->dbColumn(Entity::STATUS);
        $wfActionStateColumn        = $this->repo->workflow_action->dbColumn(Action\Entity::STATE);
        $wfActionPermissionIdColumn = $this->repo->workflow_action->dbColumn(Action\Entity::PERMISSION_ID);
        $wfStepRoleIdColumn         = $this->repo->workflow_step->dbColumn(Step\Entity::ROLE_ID);

        $workflowMerchantIdColumn = $this->repo->workflow->dbColumn(Workflow\Entity::MERCHANT_ID);

        $query->where($statusColumn, Status::PENDING)
              ->where($wfActionStateColumn, State\Name::OPEN)
              ->where($workflowMerchantIdColumn, $this->merchant->getId())
            //->where($wfActionPermissionIdColumn, $permissionId)
              ->whereIn($wfStepRoleIdColumn, $roleIds);

        if ($userId !== null)
        {
            $this->filterCompletedCheckerId($query, $userId);
        }
    }

    protected function filterCompletedCheckerId(BuilderEx $query, array $checkerId)
    {
        $actionCheckerTable = $this->repo->action_checker->getTableName();

        $actionCheckerId = $this->repo->action_checker->dbColumn(Checker\Entity::ID);

        $query->leftJoin(
            $actionCheckerTable,
            function(JoinClause $join) use ($checkerId)
            {
                $wfActionIdColumn = $this->repo->workflow_action->dbColumn(Step\Entity::ID);
                $wfStepIdColumn   = $this->repo->workflow_step->dbColumn(Step\Entity::ID);

                $actionCheckerStepId    = $this->repo->action_checker->dbColumn(Checker\Entity::STEP_ID);
                $actionCheckerActionId  = $this->repo->action_checker->dbColumn(Checker\Entity::ACTION_ID);
                $actionCheckerCheckerId = $this->repo->action_checker->dbColumn(Checker\Entity::CHECKER_ID);

                $join->on($wfActionIdColumn, '=', $actionCheckerActionId)
                     ->on($wfStepIdColumn, '=', $actionCheckerStepId)
                     ->whereIn($actionCheckerCheckerId, $checkerId);

            })
            ->whereNull($actionCheckerId);
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
            function (JoinClause $join)
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

        // Must join fund_account to join contact
        $this->joinQueryFundAccount($query);

        $query->join(
            $contactTable,
            function (JoinClause $join)
            {
                $contactIdColumn    = $this->repo->contact->dbColumn(Contact\Entity::ID);
                $faSourceIdColumn   = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
                $faSourceTypeColumn = $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

                $join->on($contactIdColumn, $faSourceIdColumn);
                $join->where($faSourceTypeColumn, E::CONTACT);
            });
    }

    protected function joinQueryWorkflowAction(BuilderEx $query)
    {
        $wfActionTable = $this->repo->workflow_action->getTableName();
        $workflowTable = $this->repo->workflow->getTableName();

        if ($query->hasJoin($wfActionTable) === true)
        {
            return;
        }

        $query->join(
            $wfActionTable,
            function(JoinClause $join)
            {
                $entityIdColumn   = $this->repo->workflow_action->dbColumn(Action\Entity::ENTITY_ID);
                $entityNameColumn = $this->repo->workflow_action->dbColumn(Action\Entity::ENTITY_NAME);

                $idColumn = $this->dbColumn(Entity::ID);

                $join->on($idColumn, $entityIdColumn)
                     ->where($entityNameColumn, E::PAYOUT);
            });

        $query->join(
            $workflowTable,
            function(JoinClause $join)
            {
                $workflowIdColumn               = $this->repo->workflow->dbColumn(Workflow\Entity::ID);
                $workflowActionWorkflowIdColumn = $this->repo->workflow_action->dbColumn(Action\Entity::WORKFLOW_ID);

                $join->on($workflowIdColumn, $workflowActionWorkflowIdColumn);
            });

        $this->repo->workflow_action->joinQueryWorkflowStep($query);
    }

    /**
     * Adds the below params(representative -- ignore the balance table)
     *
     * select `payouts`.* from `payouts`
     * inner join `balance` on
     *      `balance`.`id` = `payouts`.`balance_id`
     * inner join `workflow_entity_map` on
     *      `payouts`.`id` = `workflow_entity_map`.`entity_id`
     *      and `workflow_entity_map`.`entity_type` = ?
     * inner join `workflow_state_map` on
     *      `workflow_entity_map`.`workflow_id` = `workflow_state_map`.`workflow_id`
     *      and `workflow_state_map`.`status` = ? and `workflow_state_map`.`actor_type_value` in (?)
     * where
     *      `payouts`.`merchant_id` = ?
     *      and `balance`.`type` = ?
     *      and `payouts`.`status` = ?
     *      and `payouts`.`merchant_id` = ?
     *
     * @param BuilderEx $query
     * @param array $roleIds
     */
    protected function joinQueryWorkflowServiceEntities(BuilderEx $query, array $roleIds, $status = 'created')
    {
        $entityMapTable         = $this->repo->workflow_entity_map->getTableName();
        $workflowStateMapTable  = $this->repo->workflow_state_map->getTableName();

        if ($query->hasJoin($entityMapTable) === true)
        {
            return;
        }

        $query->join(
            $entityMapTable,
            function(JoinClause $join)
            {
                $entityIdColumn   = $this->repo->workflow_entity_map->dbColumn(Workflow\Service\EntityMap\Entity::ENTITY_ID);
                $entityNameColumn = $this->repo->workflow_entity_map->dbColumn(Workflow\Service\EntityMap\Entity::ENTITY_TYPE);

                $idColumn = $this->dbColumn(Entity::ID);

                $join->on($idColumn, $entityIdColumn)
                     ->where($entityNameColumn, '=',E::PAYOUT);
            });

        $query->join(
            $workflowStateMapTable,
            function(JoinClause $join) use ($roleIds, $status)
            {
                $entityMapWorkflowIdColumn = $this->repo->workflow_entity_map->dbColumn(Workflow\Service\EntityMap\Entity::WORKFLOW_ID);

                $roleColumn = $this->repo->workflow_state_map->dbColumn(Workflow\Service\StateMap\Entity::ACTOR_TYPE_VALUE);
                $statusColumn = $this->repo->workflow_state_map->dbColumn(Workflow\Service\StateMap\Entity::STATUS);
                $workflowIdColumn = $this->repo->workflow_state_map->dbColumn(Workflow\Service\StateMap\Entity::WORKFLOW_ID);

                $query = $join->on($entityMapWorkflowIdColumn, $workflowIdColumn)
                     ->where($statusColumn, '=', $status);
                if(empty($roleIds) === false)
                {
                    $query->whereIn($roleColumn, $roleIds);
                }
            });
    }

    protected function joinQueryWorkflowEntityMap(BuilderEx $query)
    {
        $entityMapTable = $this->repo->workflow_entity_map->getTableName();

        if ($query->hasJoin($entityMapTable) === true)
        {
            return;
        }

        $query->join(
            $entityMapTable,
            function(JoinClause $join)
            {
                $entityIdColumn   = $this->repo->workflow_entity_map->dbColumn(Workflow\Service\EntityMap\Entity::ENTITY_ID);
                $entityNameColumn = $this->repo->workflow_entity_map->dbColumn(Workflow\Service\EntityMap\Entity::ENTITY_TYPE);

                $idColumn = $this->dbColumn(Entity::ID);

                $join->on($idColumn, $entityIdColumn)
                    ->where($entityNameColumn, '=',E::PAYOUT);
            });
    }

    protected function joinOnUniqueWorkflows(BuilderEx $query, $status = 'created')
    {
        $workflowIdColumn       = $this->repo->workflow_state_map->dbColumn(Workflow\Service\StateMap\Entity::WORKFLOW_ID);
        $statusColumn           = $this->repo->workflow_state_map->dbColumn(Workflow\Service\StateMap\Entity::STATUS);
        $roleColumn             = $this->repo->workflow_state_map->dbColumn(Workflow\Service\StateMap\Entity::ACTOR_TYPE_VALUE);

        $nestedSelectAttr = [
            $workflowIdColumn,
            $roleColumn
        ];

        $nestedQuery = $this->repo->workflow_state_map->newQuery()
            ->distinct()
            ->select($nestedSelectAttr)
            ->where($statusColumn, '=', $status);

        $query->joinSub($nestedQuery, 'unique_workflows', function ($join) {
            $entityWorkflowId = $this->repo->workflow_entity_map->dbColumn(Workflow\Service\EntityMap\Entity::WORKFLOW_ID);
            $join->on($entityWorkflowId, '=', 'unique_workflows.workflow_id');
        });
    }

    protected function joinQueryBalance(BuilderEx $query)
    {
        $balanceTable = $this->repo->balance->getTableName();

        if ($query->hasJoin($balanceTable) === true)
        {
            return;
        }

        $query->join(
            $balanceTable,
            function(JoinClause $join)
            {
                $balanceIdColumn       = $this->repo->balance->dbColumn(Balance\Entity::ID);
                $payoutBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);

                $join->on($balanceIdColumn, $payoutBalanceIdColumn);
            });
    }

    protected function leftJoinQueryPayoutSource(BuilderEx $query)
    {
        $payoutSourceTable = $this->repo->payout_source->getTableName();

        if ($query->hasJoin($payoutSourceTable) === true)
        {
            return;
        }
        $query->leftJoin(
            $payoutSourceTable,
            function(JoinClause $join)
            {
                $payoutSourcePayoutIdColumn = $this->repo->payout_source->dbColumn(PayoutSource\Entity::PAYOUT_ID);
                $payoutIdColumn             = $this->dbColumn(Entity::ID);

                $join->on($payoutSourcePayoutIdColumn, $payoutIdColumn);
            });
    }

    protected function joinQueryPayoutSource(BuilderEx $query)
    {
        $payoutSourceTable = $this->repo->payout_source->getTableName();

        if ($query->hasJoin($payoutSourceTable) === true)
        {
            return;
        }

        $query->join(
            $payoutSourceTable,
            function(JoinClause $join)
            {
                $payoutSourcePayoutIdColumn = $this->repo->payout_source->dbColumn(PayoutSource\Entity::PAYOUT_ID);
                $payoutIdColumn             = $this->dbColumn(Entity::ID);

                $join->on($payoutSourcePayoutIdColumn, $payoutIdColumn);
            });
    }

    protected function joinQueryPayoutDetails(BuilderEx $query)
    {
        $payoutDetailsTable = $this->repo->payouts_details->getTableName();

        if ($query->hasJoin($payoutDetailsTable) === true)
        {
            return;
        }

        $query->join(
            $payoutDetailsTable,
            function(JoinClause $join)
            {
                $payoutDetailPayoutIdColumn = $this->repo->payouts_details->dbColumn(PayoutDetailsEntity::PAYOUT_ID);
                $payoutIdColumn             = $this->dbColumn(Entity::ID);

                $join->on($payoutDetailPayoutIdColumn, $payoutIdColumn);
            });
    }

    /**
     * {@inheritDoc}
     */
    protected function modifyQueryForIndexing(BuilderEx $query)
    {
        // Eager loading relation is optimal during bulk indexing.
        $query->with('fundAccount.contact');
    }

    /**
     * {@inheritDoc}
     */
    protected function serializeForIndexing(Base\PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        $fa = $entity->fundAccount;

        if (($fa === null) or ($fa->getSourceType() !== E::CONTACT))
        {
            // I.e. this document will not be indexed.
            return [];
        }

        /** @var $contact Contact\Entity */
        $contact = $fa->source;
        $balance = $entity->balance;

        $serialized[Entity::PRODUCT]       = $balance->getType();
        $serialized[Entity::CONTACT_NAME]  = $contact->getName();
        $serialized[Entity::CONTACT_EMAIL] = $contact->getEmail();
        $serialized[Entity::CONTACT_TYPE]  = $contact->getType();
        $serialized[Entity::CONTACT_PHONE] = $contact->getContact();
        $serialized[Entity::FUND_ACCOUNT_NUMBER] = $fa->getAccountDestinationAsText(false);

        if ($entity->payoutSources !== null && $entity->payoutSources->count() > 0)
        {
            $source_type = [];
            foreach ($entity->payoutSources as $source) {
                $source_type[] = $source['source_type'];
            }
            $serialized[Entity::SOURCE_TYPE]   = $source_type;
        }


        return $serialized;
    }

    /**
     * {@inheritDoc}
     */
    public function isEsSyncNeeded(string $action, array $dirty = null, Base\PublicEntity $entity = null): bool
    {
        //
        // Additionally checks if payout's contact exists.
        // Because otherwise there is nothing required to be indexed, rest are just common assisting attributes.
        //
        return ((($entity === null) or
                 (optional($entity->fundAccount)->getSourceType() === E::CONTACT)) and
                (parent::isEsSyncNeeded($action, $dirty, $entity) === true));
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

    protected function addQueryParamReversedFrom($query, $params)
    {
        $reversedFrom  = $params[Entity::REVERSED_FROM];
        $reversedAtCol = $this->dbColumn(Entity::REVERSED_AT);

        $query->where($reversedAtCol, '>=', $reversedFrom);
    }

    protected function addQueryParamReversedTo($query, $params)
    {
        $reversedTo    = $params[Entity::REVERSED_TO];
        $reversedAtCol = $this->dbColumn(Entity::REVERSED_AT);

        $query->where($reversedAtCol, '<=', $reversedTo);
    }

    protected function addQueryParamScheduledFrom($query, $params)
    {
        $scheduledFrom  = $params[Entity::SCHEDULED_FROM];
        $scheduledAtCol = $this->dbColumn(Entity::SCHEDULED_AT);

        $query->where($scheduledAtCol, '>=', $scheduledFrom);
    }

    protected function addQueryParamScheduledTo($query, $params)
    {
        $scheduledTo    = $params[Entity::SCHEDULED_TO];
        $scheduledAtCol = $this->dbColumn(Entity::SCHEDULED_AT);

        $query->where($scheduledAtCol, '<=', $scheduledTo);
    }

    protected function addQueryParamSortedOn($query, $params)
    {
        $sortedOn  = $params[Entity::SORTED_ON];

        $query->orderBy($sortedOn, 'desc');
    }

    protected function addQueryParamSourceId($query, $params)
    {
        $sourceId                   = $params[PayoutSource\Entity::SOURCE_ID];
        $payoutSourceSourceIdColumn = $this->repo->payout_source->dbColumn(PayoutSource\Entity::SOURCE_ID);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayoutSource($query);

        $query->where($payoutSourceSourceIdColumn, $sourceId);
    }

    protected function addQueryParamSourceType($query, $params)
    {
        $sourceType                   = $params[PayoutSource\Entity::SOURCE_TYPE];
        $payoutSourceSourceTypeColumn = $this->repo->payout_source->dbColumn(PayoutSource\Entity::SOURCE_TYPE);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayoutSource($query);

        $query->where($payoutSourceSourceTypeColumn, $sourceType);
    }

    protected function addQueryParamTdsCategoryId($query, $params)
    {
        $tdsCategoryId                    = $params[PayoutDetailsEntity::TDS_CATEGORY_ID];
        $payoutDetailsTdsCategoryIdColumn = $this->repo->payouts_details->dbColumn(PayoutDetailsEntity::TDS_CATEGORY_ID);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayoutDetails($query);

        $query->where($payoutDetailsTdsCategoryIdColumn, $tdsCategoryId);
    }

    protected function addQueryParamTaxPaymentId($query, $params)
    {
        $taxPaymentId                    = $params[PayoutDetailsEntity::TAX_PAYMENT_ID];
        $this->validateAndStripPublicTaxPaymentId($taxPaymentId);
        $payoutDetailsTaxPaymentIdColumn = $this->repo->payouts_details->dbColumn(PayoutDetailsEntity::TAX_PAYMENT_ID);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryPayoutDetails($query);

        $query->where($payoutDetailsTaxPaymentIdColumn, $taxPaymentId);
    }

    protected function validateAndStripPublicTaxPaymentId(&$taxPaymentId)
    {
        if (strpos($taxPaymentId, PayoutDetailsEntity::TAX_PAYMENT_PUBLIC_ID_PREFIX . PayoutDetailsEntity::TAX_PAYMENT_ID_DELIMITER) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TAX_PAYMENT_ID);
        }

        $publicPrefixLen = strlen(PayoutDetailsEntity::TAX_PAYMENT_PUBLIC_ID_PREFIX . PayoutDetailsEntity::TAX_PAYMENT_ID_DELIMITER);

        $taxPaymentId = substr($taxPaymentId, $publicPrefixLen);
    }

    protected function joinQueryReversal(BuilderEx $query)
    {
        $reversalTable = $this->repo->reversal->getTableName();

        if ($query->hasJoin($reversalTable) === true)
        {
            return;
        }

        $query->join(
            $reversalTable,
            function(JoinClause $join)
            {
                $reversalEntityIdColumn     = $this->repo->reversal->dbColumn(Reversal\Entity::ENTITY_ID);
                $payoutIdColumn             = $this->dbColumn(Entity::ID);

                $join->on($reversalEntityIdColumn, $payoutIdColumn);
            });
    }

    protected function addQueryParamReversalId($query, $params)
    {
        $reversalId = $params[Entity::REVERSAL_ID];
        $reversalTableIdColumn = $this->repo->reversal->dbColumn(Reversal\Entity::ID);

        $query->select($this->getTableName() . '.*');
        $this->joinQueryReversal($query);

        $query->where($reversalTableIdColumn, $reversalId);
    }

    // fetches when fee recovery was last made for CA
    public function fetchFeeLastDeductedAt(string $merchantId, string $balanceId)
    {
        $processedAtColumn = $this->dbColumn(Entity::PROCESSED_AT);
        $balanceIdColumn   = $this->dbColumn(Entity::BALANCE_ID);
        $purposeColumn     = $this->dbColumn(Entity::PURPOSE);
        $statusColumn      = $this->dbColumn(Entity::STATUS);

        return $this->newQuery()
                    ->select($processedAtColumn)
                    ->merchantId($merchantId)
                    ->where($balanceIdColumn, '=', $balanceId)
                    ->where($purposeColumn, '=', Purpose::RZP_FEES)
                    ->where($statusColumn, '=', Status::PROCESSED)
                    ->orderBy($processedAtColumn, 'desc')
                    ->limit(1)
                    ->first();
    }

    // Fetch Fund Management Payouts within a certain interval
    public function fetchFundManagementPayoutsWithinRange(string $merchantId, $retrievalThreshold)
    {
        $idColumn          = $this->dbColumn(Entity::ID);
        $amountColumn      = $this->dbColumn(Entity::AMOUNT);
        $statusColumn      = $this->dbColumn(Entity::STATUS);
        $purposeColumn     = $this->dbColumn(Entity::PURPOSE);
        $createdAtColumn   = $this->dbColumn(Entity::CREATED_AT);
        $reversedAtColumn  = $this->dbColumn(Entity::REVERSED_AT);
        $initiatedAtColumn = $this->dbColumn(Entity::INITIATED_AT);
        $processedAtColumn = $this->dbColumn(Entity::PROCESSED_AT);

        $payoutSelectAttributes = [
            $idColumn,
            $amountColumn,
            $statusColumn,
            $processedAtColumn,
            $reversedAtColumn,
            $initiatedAtColumn,
            $createdAtColumn
        ];

        $endTime   = Carbon::now(Timezone::IST)->getTimestamp();
        $startTime = Carbon::now(Timezone::IST)->subSeconds($retrievalThreshold)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($payoutSelectAttributes)
                    ->merchantId($merchantId)
                    ->where($purposeColumn, '=', Purpose::RZP_FUND_MANAGEMENT)
                    ->whereBetween($createdAtColumn, [$startTime, $endTime])
                    ->whereIn($statusColumn, [Status::PROCESSED, Status::ON_HOLD, Status::INITIATED, Status::REVERSED])
                    ->get();
    }

    // Not checking for status here, because payouts could be initiated or processed.
    public function fetchFeesForPayoutIds(array $payoutIds, $merchantId, $balanceId)
    {
        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsFeesColumn          = $this->repo->payout->dbColumn(Entity::FEES);
        $payoutsBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutsInitiatedAtColumn   = $this->repo->payout->dbColumn(Entity::INITIATED_AT);


        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->selectRaw(' SUM(' . $payoutsFeesColumn . ') AS fees')
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, '=', $balanceId)
                    ->whereIn($payoutsIdColumn, $payoutIds)
                    ->whereNotNull($payoutsInitiatedAtColumn)
                    ->first();
    }

    public function fetchFeesForFailedPayoutIds(array $failedPayoutIds, $merchantId, $balanceId)
    {
        $payoutsIdColumn        = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsFeesColumn      = $this->repo->payout->dbColumn(Entity::FEES);
        $payoutsBalanceIdColumn = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutsFailedAtColumn   = $this->repo->payout->dbColumn(Entity::FAILED_AT);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->selectRaw(' SUM(' . $payoutsFeesColumn . ') AS fees')
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, '=', $balanceId)
                    ->whereIn($payoutsIdColumn, $failedPayoutIds)
                    ->whereNotNull($payoutsFailedAtColumn)
                    ->first();
    }

    public function fetchMIDsWithBatchSubmittedPayouts()
    {
        $statusColumn = $this->repo->payout->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);
        $isPayoutService = $this->repo->payout->dbColumn(Entity::IS_PAYOUT_SERVICE);

        return $this->newQuery()
                    ->select($merchantIdColumn)
                    ->where($statusColumn, '=', Status::BATCH_SUBMITTED)
                    ->where($isPayoutService, '=', 0)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function getBatchSubmittedPayoutIds(string $merchantId, $limit = self::BATCH_PAYOUTS_FETCH_LIMIT)
    {
        $idColumn = $this->repo->payout->dbColumn(Entity::ID);
        $statusColumn = $this->repo->payout->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($idColumn)
                    ->where($statusColumn, '=', Status::BATCH_SUBMITTED)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->limit($limit)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function getScheduledPayoutsToBeProcessed($balanceIdsWhitelist, $balanceIdsBlacklist)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $payoutsIdColumn               = $this->repo->payout->dbColumn(Entity::ID);
        $payoutAmountColumn            = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutStatusColumn            = $this->repo->payout->dbColumn(Entity::STATUS);
        $payoutsBalanceIdColumn        = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutsScheduledAtColumn      = $this->repo->payout->dbColumn(Entity::SCHEDULED_AT);
        $payoutsIsPayoutServiceColumn  = $this->repo->payout->dbColumn(Entity::IS_PAYOUT_SERVICE);
        $payoutsMerchantIdColumn       = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($payoutsBalanceIdColumn, $payoutStatusColumn, $payoutsIdColumn, $payoutAmountColumn, $payoutsIsPayoutServiceColumn,$payoutsMerchantIdColumn)
                      ->where($payoutsScheduledAtColumn, '<', $currentTimeStamp)
                      ->whereIn($payoutStatusColumn, [Status::SCHEDULED, Status::PENDING]);

        if (empty ($balanceIdsWhitelist) === false)
        {
            $query->whereIn($payoutsBalanceIdColumn, $balanceIdsWhitelist);
        }

        if (empty($balanceIdsBlacklist) === false)
        {
            $query->whereNotIn($payoutsBalanceIdColumn, $balanceIdsBlacklist);
        }

        return $query->limit(self::SCHEDULED_PAYOUTS_FETCH_LIMIT)
                     ->get();
    }

    public function getScheduledPayoutsToBeProcessedForMerchants($merchantIdList, $status = Status::SCHEDULED, $limit = self::SCHEDULED_PAYOUTS_FETCH_LIMIT)
    {
        $currentTimeStamp = Carbon::now(Timezone::IST)->getTimestamp();

        $payoutsIdColumn               = $this->repo->payout->dbColumn(Entity::ID);
        $payoutAmountColumn            = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutStatusColumn            = $this->repo->payout->dbColumn(Entity::STATUS);
        $payoutsBalanceIdColumn        = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutsScheduledAtColumn      = $this->repo->payout->dbColumn(Entity::SCHEDULED_AT);
        $payoutsIsPayoutServiceColumn  = $this->repo->payout->dbColumn(Entity::IS_PAYOUT_SERVICE);
        $payoutsMerchantIdColumn       = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($payoutsBalanceIdColumn, $payoutStatusColumn, $payoutsIdColumn, $payoutAmountColumn, $payoutsIsPayoutServiceColumn,$payoutsMerchantIdColumn)
                      ->where($payoutsScheduledAtColumn, '<', $currentTimeStamp)
                      ->where($payoutStatusColumn, '=', $status)
                      ->whereIn($payoutsMerchantIdColumn, $merchantIdList);

        return $query->limit($limit)
                     ->get();
    }

    public function fetchPayoutsPurposeToTrim($merchantIds,
                                              $from,
                                              $to,
                                              $limit = 1000)
    {
        return $this->newQuery()
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->where(Entity::CREATED_AT, '>=', $from)
                    ->where(Entity::CREATED_AT, '<=', $to)
                    ->where(
                        DB::raw('CHAR_LENGTH(' . Entity::PURPOSE . ')'),
                        '>',
                        DB::raw('CHAR_LENGTH(trim(replace(' . Entity::PURPOSE . ',"\n"," ")))')
                    )
                    ->limit($limit)
                    ->get();
    }

    /**
     * This will fetch all payouts in created state which are created in the last 24 hours
     * after 05-02-2022 and where transaction_id is null.
     * @param int $days
     * @param int $limit
     * @return mixed
     */
    public function fetchCreatedPayoutsAndTxnIdNullBetweenTimestamp(int $days, int $limit)
    {
        $currentTime = Carbon::now(Timezone::IST)->subMinutes(15)->subDays($days);
        $currentTimeStamp = $currentTime->getTimestamp();

        $lastTimestamp = $currentTime->subDay()->getTimestamp();
        $txnIdFillingTimestamp = Carbon::createFromFormat('d-m-Y', '05-02-2022', Timezone::IST)->getTimestamp();

        if ($lastTimestamp < $txnIdFillingTimestamp)
        {
            $lastTimestamp = $txnIdFillingTimestamp;
        }

        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $payoutTransactionIdColumn = $this->repo->payout->dbColumn(Entity::TRANSACTION_ID);
        $payoutStatusColumn        = $this->repo->payout->dbColumn(Entity::STATUS);
        $payoutBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutCreatedColumn       = $this->dbColumn(Entity::CREATED_AT);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutBalanceIdColumn)
                    ->select($payoutAttrs)
                    ->where($payoutStatusColumn, '=', Status::CREATED)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($payoutTransactionIdColumn)
                    ->whereBetween($payoutCreatedColumn, [$lastTimestamp, $currentTimeStamp])
                    ->limit($limit)
                    ->get();
    }

    /**
     * This will fetch all payouts in created state where id is in the given list of ids
     * and where transaction_id is null.
     * @param array $ids
     * @return mixed
     */
    public function fetchCreatedPayoutsWhereTxnIdNullAndIdsIn(array $ids)
    {
        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $payoutIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutTransactionIdColumn = $this->repo->payout->dbColumn(Entity::TRANSACTION_ID);
        $payoutBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutBalanceIdColumn)
                    ->select($payoutAttrs)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($payoutTransactionIdColumn)
                    ->whereIn($payoutIdColumn, $ids)
                    ->get();
    }

    /**
     * get yesterday's total payout amount, fee and tax count
     *
     */
    public function getPayoutAmountFeeAndTaxCountForYesterday()
    {
        $yesterdayStartOfDay = Carbon::yesterday(Timezone::IST)->startOfDay()->getTimestamp();
        $yesterdayEndOfDay = Carbon::yesterday(Timezone::IST)->endOfDay()->getTimestamp();

        return $this->getPayoutAmountFeeAndTaxCountBetweenTimestamp($yesterdayStartOfDay, $yesterdayEndOfDay);
    }

    /**
     * get total payout amount, fee and tax count between two time stamp
     *
     * @param $from
     * @param $to
     *
     * @return array
     */
    public function getPayoutAmountFeeAndTaxCountBetweenTimestamp($from, $to)
    {
        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);

        $merchantIdColumn           = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantEmailColumn        = $this->repo->merchant->dbColumn(Merchant\Entity::EMAIL);

        $payoutStatusColumn         = $this->repo->payout->dbColumn(Entity::STATUS);
        $payoutsBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutsMerchantIdColumn    = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
                    ->join(Table::MERCHANT, $merchantIdColumn, '=', $payoutsMerchantIdColumn)
                    ->betweenTime($from, $to)
                    ->selectRaw('COALESCE(ROUND(SUM(' . Entity::AMOUNT . '* 1.0 / 1000000000), 2), 0)AS payout_amount_cr' . ','.
                                'COALESCE(ROUND(SUM(' . Entity::FEES . ')/100, 2), 0)AS payout_fee_collected' . ','.
                                'COUNT(' . Entity::AMOUNT . ') AS payout_count')
                    ->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_created_at_index)'))
                    ->where($payoutStatusColumn, '=', Status::PROCESSED)
                    ->where($merchantEmailColumn, 'not like', '%@razorpay.com')
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->first();
    }

    public function fetchPayoutsWithStatus(array $statuses,
                                                 $toTimestamp = 0,
                                                 $fromTimestamp = 0,
                                           array $merchantIdsWhitelist = [],
                                           array $merchantIdsBlacklist = [])
    {
        $isPayoutService = $this->repo->payout->dbColumn(Entity::IS_PAYOUT_SERVICE);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($this->getTableName() . ".*")
            ->with(['balance', 'merchant', 'merchant.org'])
            ->WhereIn(Entity::STATUS, $statuses)
            ->where($isPayoutService, '=', 0)
            ->whereNull(Entity::FTS_TRANSFER_ID);

        $merchantIdColumn = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        if (empty ($merchantIdsWhitelist) === false)
        {
            $query->whereIn($merchantIdColumn, $merchantIdsWhitelist);
        }

        if (empty($merchantIdsBlacklist) === false)
        {
            $query->whereNotIn($merchantIdColumn, $merchantIdsBlacklist);
        }

        $this->joinQueryBalance($query);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $query->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING);

        $createdAtColumn = $this->repo->payout->dbColumn(Entity::CREATED_AT);

        if ($toTimestamp != 0)
        {
            $query->where($createdAtColumn, '<=', $toTimestamp);
        }

        if ($fromTimestamp != 0)
        {
            $query->where($createdAtColumn, '>=', $fromTimestamp);
        }

        return $query->limit(self::STUCK_PAYOUTS_FETCH_LIMIT)
            ->get();
    }

    /**
     * get yesterday's total payout amount and tax count for merchants
     * sorted in descending order by payout count and payout amount
     *
     * @param int $limit
     *
     * @return array
     */
    public function getYesterdayMerchantsPayoutAmountAndTaxCountGroupByMerchant(int $limit)
    {
        $from                          = Carbon::yesterday(Timezone::IST)->startOfDay()->getTimestamp();
        $to                            = Carbon::yesterday(Timezone::IST)->endOfDay()->getTimestamp();

        $balanceIdColumn               = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn             = $this->repo->balance->dbColumn(Balance\Entity::TYPE);

        $merchantIdColumn              = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantNameColumn            = $this->repo->merchant->dbColumn(Merchant\Entity::NAME);
        $merchantEmailColumn           = $this->repo->merchant->dbColumn(Merchant\Entity::EMAIL);
        $merchantWebsiteColumn         = $this->repo->merchant->dbColumn(Merchant\Entity::WEBSITE);
        $merchantBillingLabelColumn    = $this->repo->merchant->dbColumn(Merchant\Entity::BILLING_LABEL);
        $merchantBusinessBankingColumn = $this->repo->merchant->dbColumn(Merchant\Entity::BUSINESS_BANKING);

        $payoutStatusColumn            = $this->repo->payout->dbColumn(Entity::STATUS);
        $payoutsBalanceIdColumn        = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutsMerchantIdColumn       = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        $dataSortedByPayoutCount = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                                        ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
                                        ->join(Table::MERCHANT, $merchantIdColumn, '=', $payoutsMerchantIdColumn)
                                        ->betweenTime($from, $to)
                                        ->selectRaw(
                                            $payoutsMerchantIdColumn . ' as x_merchant_id' . ',' .
                                            'COALESCE(' . $merchantBillingLabelColumn . ',' . $merchantNameColumn . ') as x_merchant_display_name' . ',' .
                                            'COALESCE(' . $merchantWebsiteColumn . ',"Not Available") as x_merchant_website,' .
                                            'COUNT(*) AS payout_count' . ',' .
                                            'COALESCE(ROUND(SUM(' . Entity::AMOUNT . '* 1.0 / 1000000000), 2), 0) AS payout_amount_cr'
                                        )
                                        ->where($merchantBusinessBankingColumn, '=', 1)
                                        ->where($payoutStatusColumn, '=', Status::PROCESSED)
                                        ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                                        ->where($merchantEmailColumn, 'not like', '%@razorpay.com')
                                        ->groupBy(
                                            'x_merchant_id',
                                            'x_merchant_display_name',
                                            'x_merchant_website')
                                        ->orderBy('payout_count', 'desc')
                                        ->limit($limit)
                                        ->get();

        $dataSortedByPayoutAmount = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                                         ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
                                         ->join(Table::MERCHANT, $merchantIdColumn, '=', $payoutsMerchantIdColumn)
                                         ->betweenTime($from, $to)
                                         ->selectRaw(
                                             $payoutsMerchantIdColumn . ' as x_merchant_id' . ',' .
                                             'COALESCE(' . $merchantBillingLabelColumn . ',' . $merchantNameColumn . ') as x_merchant_display_name' . ',' .
                                             'COALESCE(' . $merchantWebsiteColumn . ',"Not Available") as x_merchant_website,' .
                                             'COUNT(*) AS payout_count' . ',' .
                                             'COALESCE(ROUND(SUM(' . Entity::AMOUNT . '* 1.0 / 1000000000), 2), 0) AS payout_amount_cr'
                                         )
                                         ->where($merchantBusinessBankingColumn, '=', 1)
                                         ->where($payoutStatusColumn, '=', Status::PROCESSED)
                                         ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                                         ->where($merchantEmailColumn, 'not like', '%@razorpay.com')
                                         ->groupBy(
                                             'x_merchant_id',
                                             'x_merchant_display_name',
                                             'x_merchant_website')
                                         ->orderBy('payout_amount_cr', 'desc')
                                         ->limit($limit)
                                         ->get();

        return [
            'sorted_by_payout_count'  => $dataSortedByPayoutCount,
            'sorted_by_payout_amount' => $dataSortedByPayoutAmount,
        ];
    }

    /**
     * select COUNT(*)
     * from `payouts`
     * where `payouts`.`status` = ?
     * and `payouts`.`merchant_id` = ?
     *
     * @param string $merchantId
     * @return mixed
     */
    public function fetchCountOfPendingPayoutsForMerchant(string $merchantId)
    {
        $statusColumn     = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $query = $this->newQuery()
                      ->where($statusColumn, '=', Status::PENDING)
                      ->where($merchantIdColumn, '=', $merchantId);

        return $query->count();
    }

    public function getPayoutDashboardCohortList(int $startTime, int $endTime)
    {
        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);

        $payoutCreatedColumn        = $this->dbColumn(Entity::CREATED_AT);
        $payoutsBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);

        $selectAttr                 = [
            $this->dbColumn(Entity::MERCHANT_ID),
            $this->dbColumn(Entity::USER_ID)
        ];

        return $this->newQuery()
                    ->select($selectAttr)
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->whereNotNull(Entity::USER_ID)
                    ->whereBetween($payoutCreatedColumn, [$startTime, $endTime])
                    ->groupBy(Entity::MERCHANT_ID, Entity::USER_ID)
                    ->get();
    }

    public function getPayoutAPICohortList(int $startTime, int $endTime)
    {
        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);

        $payoutCreatedColumn        = $this->dbColumn(Entity::CREATED_AT);
        $payoutsBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);

        $selectAttr                 = [
            $this->dbColumn(Entity::MERCHANT_ID)
        ];

        return $this->newQuery()
            ->select($selectAttr)
            ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
            ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
            ->whereNull(Entity::USER_ID)
            ->whereBetween($payoutCreatedColumn, [$startTime, $endTime])
            ->groupBy(Entity::MERCHANT_ID)
            ->get();
    }

    public function getCAPayoutCohortList(int $startTime, int $endTime, $surveyTTL)
    {
        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $payoutCreatedColumn        = $this->dbColumn(Entity::CREATED_AT);
        $payoutsBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);

        $selectAttr                 = [
            $this->dbColumn(Entity::MERCHANT_ID),
            $this->dbColumn(Entity::USER_ID)
        ];

        return $this->newQuery()
            ->select($selectAttr)
            ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
            ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
            ->where($balanceAccountTypeColumn, '=', Balance\AccountType::DIRECT)
            ->whereBetween($payoutCreatedColumn, [$startTime, $endTime])
            ->whereRaw("datediff(from_unixtime(?),from_unixtime(balance.created_at))%? = 0", [$endTime,  $surveyTTL])
            ->groupBy(Entity::MERCHANT_ID, Entity::USER_ID)
            ->get();
    }

    public function findPendingPayoutsSummaryForAccountNumbers(array $accountNumbers, string $merchantId)
    {
        /*
            SELECT payouts.id, payouts.amount, balance.account_number
            from payouts
            join balance
            on payouts.balance_id = balance.id
            where payouts.merchant_id = 'MID'
            and payouts.status = 'pending'
            and balance.account_number IN ('AC_NO');
         * */
        $balanceAccountNumberColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_NUMBER);

        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);

        $payoutsBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);

        $payoutsMerchantIdColumn    = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        $payoutsStatusColumn    = $this->repo->payout->dbColumn(Entity::STATUS);

        $selectAttr                 = [
            $this->dbColumn(Entity::ID),
            $this->dbColumn(Entity::AMOUNT)
        ];

        return $this->newQuery()
            ->select($selectAttr)
            ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
            ->where($payoutsMerchantIdColumn, '=', $merchantId)
            ->where($payoutsStatusColumn, '=', 'pending')
            ->whereIn($balanceAccountNumberColumn, $accountNumbers)
            ->get();
    }

    /**
     * SELECT COUNT(*)
     * FROM 'payouts'
     * WHERE 'payouts'.'merchant_id' = ?
     * AND 'payouts'.'status' = 'initiated'
     * AND 'payouts'.'created_at' = ?
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function fetchCountOfPayoutsStuckInInitiatedToday(string $merchantId)
    {
        $statusColumn     = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $createdAtColumn  = $this->dbColumn(Entity::CREATED_AT);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where($statusColumn, Status::INITIATED)
                      ->where($merchantIdColumn, $merchantId)
                      ->where($createdAtColumn, '>=', Carbon::now()->startOfDay()->timestamp);

        return $query->count();
    }

    /**
     * SELECT COUNT(*)
     * FROM 'payouts'
     * WHERE 'payouts'.'merchant_id' = ?
     * AND 'payouts'.'mode' = ?
     * AND 'payouts'.'status' = 'initiated'
     * AND 'payouts'.'created_at' = ?
     *
     * @param string $merchantId
     *
     * @param string $mode
     *
     * @return mixed
     */
    public function fetchCountOfPayoutsStuckInInitiatedTodayByMode(string $merchantId, string $mode)
    {
        $statusColumn     = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $createdAtColumn  = $this->dbColumn(Entity::CREATED_AT);
        $modeColumn       = $this->dbColumn(Entity::MODE);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where($statusColumn, Status::INITIATED)
                      ->where($merchantIdColumn, $merchantId)
                      ->where($modeColumn, $mode)
                      ->where($createdAtColumn, '>=', Carbon::now()->startOfDay()->timestamp);

        return $query->count();
    }

    public function fetchMerchantUserDataHavingPendingPayouts(array $includeMerchantIds = [], array $excludeMerchantIds = [])
    {
        /*
        select `merchant_users`.`user_id`, `users`.`name`, `users`.`email`, `merchants`.`name` as `business_name`, `payouts`.`merchant_id`, `merchant_users`.`role`,
            COUNT( payouts.id) AS payout_count, SUM( payouts.amount) AS payout_total from `payouts`
        inner join `workflow_entity_map` on `payouts`.`id` = `workflow_entity_map`.`entity_id` and `workflow_entity_map`.`entity_type` = ?
        inner join
            (select distinct `workflow_state_map`.`workflow_id`, `workflow_state_map`.`actor_type_value` from `workflow_state_map`
            where `workflow_state_map`.`status` = ?)
            as `unique_workflows` on `workflow_entity_map`.`workflow_id` = `unique_workflows`.`workflow_id`
        inner join `merchant_users` on `payouts`.`merchant_id` = `merchant_users`.`merchant_id`
        inner join `merchants` on `payouts`.`merchant_id` = `merchants`.`id`
        inner join `users` on `merchant_users`.`user_id` = `users`.`id`
            where `merchant_users`.`role` = `unique_workflows`.`actor_type_value`
                and `merchant_users`.`product` = ?
                and `payouts`.`status` = ?
                and `payouts`.`merchant_id` not in [$excludeMerchantIds]
                and `payouts`.`merchant_id` in [$includeMerchantIds]
            group by `merchant_id`, `name`, `user_id`, `name`, `email`, `role`, `business_name`
        */


        $workflowStateMapMerchantId               = $this->repo->workflow_state_map->dbColumn(WorkflowStateMap::MERCHANT_ID);
        $workflowStateMapActorTypeValue           = $this->repo->workflow_state_map->dbColumn(WorkflowStateMap::ACTOR_TYPE_VALUE);

        $userIdColumn               = $this->repo->user->dbColumn(User\Entity::ID);
        $userNameColumn             = $this->repo->user->dbColumn(User\Entity::NAME);
        $userEmailColumn            = $this->repo->user->dbColumn(User\Entity::EMAIL);

        $merchantUserUserIdColumn            = $this->repo->merchant_user->dbColumn(Merchant\MerchantUser\Entity::USER_ID);
        $merchantUserMerchantIdColumn        = $this->repo->merchant_user->dbColumn(Merchant\MerchantUser\Entity::MERCHANT_ID);
        $merchantUserProductColumn           = $this->repo->merchant_user->dbColumn(Merchant\MerchantUser\Entity::PRODUCT);
        $merchantUserRoleColumn              = $this->repo->merchant_user->dbColumn(Merchant\MerchantUser\Entity::ROLE);

        $merchantIdColumn           = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantNameColumn         = $this->repo->merchant->dbColumn(Merchant\Entity::NAME);

        $payoutStatus         = $this->dbColumn(Entity::STATUS);
        $payoutMerchantId     = $this->dbColumn(Entity::MERCHANT_ID);

        $userAttrs = [
            $merchantUserUserIdColumn,
            $userNameColumn,
            $userEmailColumn,
            $merchantNameColumn.' AS business_name',
            $payoutMerchantId,
            $merchantUserRoleColumn
        ];

        if ((new Merchant\Acs\AsvRouter\AsvRouter())->shouldRouteBeMigratedToTiDB(__FUNCTION__))
        {
            $query = $this->newQueryWithConnection(
                $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT)
            );
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getReportingReplicaConnection());
        }

        $query = $query->select($this->getTableName() . '.*')
            ->select($userAttrs)
            ->selectRaw('COUNT( payouts.' . Entity::ID . ') AS payout_count,
                           SUM( payouts.' . Entity::AMOUNT . ') AS payout_total')
            ->with(['merchant']);

        //Workflow state map has only two status processed/created
        $this->joinQueryWorkflowEntityMap($query);
        $this->joinOnUniqueWorkflows($query, Status::CREATED);

        $query->whereColumn($merchantUserRoleColumn, '=', 'unique_workflows.actor_type_value');

        $query->join(Table::MERCHANT_USER, $payoutMerchantId, '=', $merchantUserMerchantIdColumn)
            ->join(Table::MERCHANT, $payoutMerchantId, '=', $merchantIdColumn)
            ->join(Table::USER, $merchantUserUserIdColumn, '=', $userIdColumn)
            ->where($merchantUserProductColumn, Merchant\Balance\Type::BANKING)
            ->where($payoutStatus, Status::PENDING);

        if (sizeof($includeMerchantIds) != 0)
        {
            $query->whereIn($payoutMerchantId, $includeMerchantIds);
        }

        if (sizeof($excludeMerchantIds) != 0)
        {
            $query->whereNotIn($payoutMerchantId, $excludeMerchantIds);
        }

        $query->groupBy(
                Entity::MERCHANT_ID,
                Merchant\Entity::NAME,
                Merchant\MerchantUser\Entity::USER_ID,
                User\Entity::NAME, User\Entity::EMAIL,
                Merchant\MerchantUser\Entity::ROLE,
                'business_name'
            );

        return $query->get();
    }

    public function fetchPendingPayoutsToDisplay($merchantId, $userRole)
    {
/*
        select `payouts`.`id`, `contacts`.`name` as `contact_name`, `payouts`.`amount`, `payouts`.`purpose`, `payouts`.`created_at`
        from `payouts`
        inner join `workflow_entity_map` on `payouts`.`id` = `workflow_entity_map`.`entity_id`
            and `workflow_entity_map`.`entity_type` = ?
        inner join `workflow_state_map` on `workflow_entity_map`.`workflow_id` = `workflow_state_map`.`workflow_id`
            and `workflow_state_map`.`status` = ?
            and `workflow_state_map`.`actor_type_value` in (?)
        inner join `fund_accounts` on `payouts`.`fund_account_id` = `fund_accounts`.`id`
        inner join `contacts` on `fund_accounts`.`source_id` = `contacts`.`id`
        where `payouts`.`merchant_id` = ?
            and `fund_accounts`.`source_type` = ?
            and `payouts`.`status` = ?
        order by `payouts`.`created_at` desc
        limit 5
*/

        $payoutId               =       $this->dbColumn(Entity::ID);
        $payoutStatus           =       $this->dbColumn(Entity::STATUS);
        $payoutMerchantId       =       $this->dbColumn(Entity::MERCHANT_ID);
        $payoutAmount           =       $this->dbColumn(Entity::AMOUNT);
        $payoutPurpose          =       $this->dbColumn(Entity::PURPOSE);
        $payoutCreatedAt        =       $this->dbColumn(Entity::CREATED_AT);
        $payoutFundAccountId    =       $this->dbColumn(Entity::FUND_ACCOUNT_ID);

        $fundAccountId              =       $this->repo->fund_account->dbColumn(FundAccount\Entity::ID);
        $fundAccountSourceId        =       $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
        $fundAccountSourceType      =       $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $contactId                  =       $this->repo->contact->dbColumn(Contact\Entity::ID);
        $contactName                =       $this->repo->contact->dbColumn(Contact\Entity::NAME);

        $selectAttr = [
            $payoutId,
            $contactName.' AS contact_name',
            $payoutAmount,
            $payoutPurpose,
            $payoutCreatedAt
        ];

        $query = $this->newQuery()
            ->select($this->getTableName() . '.*')
            ->select($selectAttr)
            ->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_merchant_id_created_at_index)'))
            ->where($payoutMerchantId,'=',$merchantId);

        //Workflow state map has only two status processed/created
        $this->joinQueryWorkflowServiceEntities($query, [$userRole],Status::CREATED);

        $query->join(Table::FUND_ACCOUNT,$payoutFundAccountId,'=',$fundAccountId)
            ->join(Table::CONTACT,$fundAccountSourceId,'=',$contactId)
            ->where($fundAccountSourceType,'=','contact')
            ->where($payoutStatus, Status::PENDING)
            ->orderBy($payoutCreatedAt,'desc')
            ->limit(5);

        return $query->get();
    }

    public function fetchNPendingPayoutsToDisplay($merchantId, $userRole, $count = self::PENDING_PAYOUT_COUNT)
    {
        /*
            select distinct `payouts`.`id`, `contacts`.`name` as `contact_name`, `payouts`.`amount`, `payouts`.`purpose`, `payouts`.`created_at`
            from `payouts` USE INDEX (payouts_merchant_id_created_at_index)
            inner join `workflow_entity_map` on `payouts`.`id` = `workflow_entity_map`.`entity_id`
                and `workflow_entity_map`.`entity_type` = ?
            inner join `workflow_state_map` on `workflow_entity_map`.`workflow_id` = `workflow_state_map`.`workflow_id`
                and `workflow_state_map`.`status` = ?
                and `workflow_state_map`.`actor_type_value` in (?)
            inner join `fund_accounts` on `payouts`.`fund_account_id` = `fund_accounts`.`id`
            inner join `contacts` on `fund_accounts`.`source_id` = `contacts`.`id`
            where `payouts`.`merchant_id` = ?
                and `fund_accounts`.`source_type` = ?
                and `payouts`.`status` = ?
            order by `payouts`.`created_at` desc
            limit ?
        */

        $payoutId               =       $this->dbColumn(Entity::ID);
        $payoutStatus           =       $this->dbColumn(Entity::STATUS);
        $payoutMerchantId       =       $this->dbColumn(Entity::MERCHANT_ID);
        $payoutAmount           =       $this->dbColumn(Entity::AMOUNT);
        $payoutPurpose          =       $this->dbColumn(Entity::PURPOSE);
        $payoutCreatedAt        =       $this->dbColumn(Entity::CREATED_AT);
        $payoutFundAccountId    =       $this->dbColumn(Entity::FUND_ACCOUNT_ID);

        $fundAccountId              =       $this->repo->fund_account->dbColumn(FundAccount\Entity::ID);
        $fundAccountSourceId        =       $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_ID);
        $fundAccountSourceType      =       $this->repo->fund_account->dbColumn(FundAccount\Entity::SOURCE_TYPE);

        $contactId                  =       $this->repo->contact->dbColumn(Contact\Entity::ID);
        $contactName                =       $this->repo->contact->dbColumn(Contact\Entity::NAME);

        $selectAttr = [
            $payoutId,
            $contactName.' AS contact_name',
            $payoutAmount,
            $payoutPurpose,
            $payoutCreatedAt
        ];

        $query = $this->newQueryWithConnection($this->getReportingReplicaConnection())
            ->distinct()
            ->select($this->getTableName() . '.*')
            ->select($selectAttr)
            ->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_merchant_id_created_at_index)'))
            ->where($payoutMerchantId,'=',$merchantId);

        //Workflow state map has only two status processed/created
        $this->joinQueryWorkflowServiceEntities($query, [$userRole],Status::CREATED);

        $query->join(Table::FUND_ACCOUNT,$payoutFundAccountId,'=',$fundAccountId)
            ->join(Table::CONTACT,$fundAccountSourceId,'=',$contactId)
            ->where($fundAccountSourceType,'=','contact')
            ->where($payoutStatus, Status::PENDING)
            ->orderBy($payoutCreatedAt,'desc')
            ->limit($count);

        return $query->get();
    }

    public function updatePayout($payoutId, $merchantId, $updates) {
        return $this->newQuery()
            ->where(Entity::ID, $payoutId)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->update($updates);

    }

    public function fetchPayoutsWithSkip($skip, $count, $merchantIds=null, $startTime=null, $endTime=null)
    {
        $payoutCreatedAtColumn   = $this->dbColumn(Entity::CREATED_AT);
        $payoutMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $payouts = $this->newQuery();

        if($merchantIds != null)
        {
            $payouts->whereIn($payoutMerchantIdColumn, $merchantIds);
        }

        if($startTime != null)
        {
            $payouts->where($payoutCreatedAtColumn, '>=', $startTime);
        }

        if($endTime != null)
        {
            $payouts->where($payoutCreatedAtColumn, '<=', $endTime);
        }

        return $payouts->take($count)
                        ->skip($skip)
                        ->oldest($payoutCreatedAtColumn)
                        ->get()
                        ->pluck(Entity::ID)
                        ->toArray();
    }

    public function fetchPayoutsForMerchantIdWithSkip($merchantId, $skip, $count)
    {
        $payoutCreatedAtColumn   = $this->dbColumn(Entity::CREATED_AT);

        $payouts = $this->newQuery()
            ->merchantId($merchantId)
            ->take($count)
            ->skip($skip)
            ->oldest($payoutCreatedAtColumn)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();

        return $payouts;
    }

    // returns all processed payouts for given mode with given narration which were created in
    // between those two timestamp
    public function fetchTestPayouts(string $merchantId, string $mode, string $narration)
    {
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $createdAtColumn = $this->dbColumn(Entity::CREATED_AT);
        $narrationColumn = $this->dbColumn(Entity::NARRATION);
        $modeColumn = $this->dbColumn(Entity::MODE);

        $currentTime = Carbon::now()->getTimestamp();
        $startTime = Carbon::createFromTimestamp(
            $currentTime,
            Timezone::IST)
            ->subMinutes(45)
            ->getTimestamp();
        $endTime = Carbon::createFromTimestamp(
            $currentTime,
            Timezone::IST)
            ->subMinutes(40)
            ->getTimestamp();


        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where($statusColumn, Status::PROCESSED)
            ->where($merchantIdColumn, $merchantId)
            ->where($modeColumn, $mode)
            ->where($narrationColumn, $narration)
            ->whereBetween($createdAtColumn, [$startTime, $endTime])
            ->get();
    }

    // Fetch data to migrate to Payout Service.
    public function getSourceTableData(array $queryParams)
    {
        $id             = $queryParams[self::ID];
        $balanceId      = $queryParams[Entity::BALANCE_ID];
        $merchantId     = $queryParams[Entity::MERCHANT_ID];
        $createdAtEnd   = $queryParams[self::END_TIMESTAMP];
        $createdAtStart = $queryParams[self::CREATED_AT];
        $limit          = $queryParams[self::LIMIT];

        if (array_key_exists('buffer', $queryParams) === true)
        {
            $createdAtStart = $createdAtStart - $queryParams['buffer'];
        }

        $connectionType = $this->getPaymentFetchReplicaConnection();

        if ($this->isExperimentEnabledForId(self::PAYMENT_FETCH_QUERIES_TIDB_MIGRATION, __FUNCTION__) === true)
        {
            $connectionType = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);
        }

        return $this->newQueryWithConnection($connectionType)
                    ->whereBetween(Entity::CREATED_AT, [$createdAtStart, $createdAtEnd])
                    ->where(Entity::ID, '>', $id)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::BALANCE_ID, $balanceId)
                    ->orderBy(Entity::ID, 'asc')
                    ->limit($limit)
                    ->get();
    }

    public function dedupeAtPayoutService(array $ids)
    {
        $query = $this->newQueryWithConnection($this->getPayoutsServiceConnection());

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $query->from('ps_payouts');
        }

        return $query->select(Entity::ID)
                     ->whereIn(Entity::ID, $ids)
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    // Does a bulk insert into Payout Service DB.
    public function insertIntoPayoutServiceDB(string $destinationTable, $data)
    {
        $this->newQueryWithConnection($this->getPayoutsServiceConnection())
             ->from($destinationTable)
             ->insert($data);
    }

    // Does an update on Payout Service DB.
    public function updateInPayoutServiceDB(string $destinationTable, string $id, $data)
    {
        $this->newQueryWithConnection($this->getPayoutsServiceConnection())
             ->from($destinationTable)
             ->where('id', $id)
             ->update($data);
    }

    // Opens a DB transaction on PS DB connection.
    public function dbTransactionOnPS(callable $callback)
    {
        \DB::connection($this->getPayoutsServiceConnection())
           ->transaction($callback);
    }

    public function getPayoutServicePayout(string $id)
    {
        $tableName = Table::PAYOUT;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_payouts';
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where id = '$id' limit 1");
    }

    public function getPayoutServicePayoutIds(array $ids)
    {
        $tableName = Table::PAYOUT;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_payouts';
        }

        $payoutServicePayoutIds = [];

        while (empty($ids) === false)
        {
            $limitIds = array_slice($ids, 0, self::PAYOUT_SERVICE_PAYOUTS_FETCH_LIMIT);

            $payoutIdsToFetch = implode("','", $limitIds);

            $psPayoutIds = \DB::connection($this->getPayoutsServiceConnection())
                                           ->select("select id from $tableName where id in ('$payoutIdsToFetch')");

            $payoutServicePayoutIds = array_merge($payoutServicePayoutIds, array_column($psPayoutIds, 'id'));

            $ids = array_diff($ids, $limitIds);
        }

        return $payoutServicePayoutIds;
    }

    public function getPayoutServiceIdempotencyKey(string $idempotencyKey, string $merchantId)
    {
        $tableName = Table::IDEMPOTENCY_KEY;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_idempotency_keys';
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where idempotency_key = '$idempotencyKey' and " .
                           "merchant_id = '$merchantId' and source_type = 'payout' order by id desc limit 1");
    }

    public function getPayoutServicePayoutMetaDataForDualWrite(string $payoutId, string $metaName = 'dual_write')
    {
        $tableName = 'payout_meta_temporary';

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where payout_id = '$payoutId' and meta_name = '$metaName'");
    }

    public function getPayoutServicePayoutLogs(string $payoutId)
    {
        $tableName = 'payout_logs';

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_' . $tableName;
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where payout_id = '$payoutId' order by created_at asc");
    }

    public function fetchCountOfProcessedPayoutsInLast24Hours(string $merchantId)
    {
        $narration        = Payout\Core::NARRATION_ICICI;
        $statusColumn     = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $createdAtColumn  = $this->dbColumn(Entity::CREATED_AT);
        $narrationColumn  = $this->dbColumn(Entity::NARRATION);

        $previousDayTimeStamp = Carbon::now(Timezone::IST)->subHours(24)->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where($createdAtColumn,'>=', $previousDayTimeStamp)
                      ->where($statusColumn, Status::PROCESSED)
                      ->where($merchantIdColumn, $merchantId)
                      ->where($narrationColumn, $narration);

        return $query->count();
    }

    public function getPayoutsSummaryForBatchId(string $batchId, string $mode)
    {
        /*
            SELECT payouts.id, payouts.status, payouts.amount
            from payouts
            where payouts.batch_id = 'batch_id';
         * */
        $payoutBatchIdColumn            = $this->repo->payout->dbColumn(Entity::BATCH_ID);

        $idColumn   = $this->repo->payout->dbColumn(Entity::ID);
        $statusColumn = $this->repo->payout->dbColumn(Entity::STATUS);
        $amountColumn = $this->repo->payout->dbColumn(Entity::AMOUNT);

        return $this->newQueryWithConnection($this->getSlaveConnection($mode))
            ->select($idColumn, $statusColumn, $amountColumn)
            ->where($payoutBatchIdColumn, '=', $batchId)
            ->get();
    }

    /**
     * SELECT
     * DISTINCT b.merchant_id, b.id as balance_id, b.account_number
     * FROM payouts p
     * INNER JOIN balance b ON b.id = p.balance_id
     * WHERE p.purpose = 'rzp_fees' AND p.status = 'queued' AND b.merchant_id IN (
     *      SELECT DISTINCT entity_id FROM features
     *      WHERE name = 'payout' AND entity_type = 'merchant'
     * )
     * AND b.merchant_id NOT IN (
     *      SELECT DISTINCT entity_id FROM features
     *      WHERE name = 'excluded_from_ca_billing' AND entity_type = 'merchant'
     * )
     * AND b.type = 'banking'
     * AND b.account_type = 'direct'
     * AND b.account_number IS NOT NULL
     * (optional) AND b.balance < $balanceAmount
     *
     * DBA: https://razorpay.atlassian.net/browse/DBOPS-3728
     */
    public function fetchEligibleBalancesForLowBalanceAlertAndBlocking($balanceAmount = null)
    {
        // balance columns
        $balanceId              = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceMerchantId      = $this->repo->balance->dbColumn(Balance\Entity::MERCHANT_ID);
        $balanceType            = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountType     = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);
        $balanceAccountNumber   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_NUMBER);
        $balanceCol             = $this->repo->balance->dbColumn(Balance\Entity::BALANCE);

        // payouts columns
        $payoutPurpose     = $this->repo->payout->dbColumn(Payout\Entity::PURPOSE);
        $payoutStatus      = $this->repo->payout->dbColumn(Payout\Entity::STATUS);
        $payoutBalanceId   = $this->repo->payout->dbColumn(Payout\Entity::BALANCE_ID);

        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->join(Table::BALANCE, $balanceId, '=', $payoutBalanceId)
            ->where($payoutPurpose, '=', Payout\Purpose::RZP_FEES)
            ->where($payoutStatus, '=', Payout\Status::QUEUED)
            ->whereIn($balanceMerchantId, function($query)
            {
                $query->select(Feature\Entity::ENTITY_ID)
                    ->distinct()
                    ->from(Table::FEATURE)
                    ->where(Feature\Entity::NAME, '=', Feature\Constants::PAYOUT)
                    ->where(Feature\Entity::ENTITY_TYPE, '=', Feature\Constants::MERCHANT);
            })
            ->whereNotIn($balanceMerchantId, function($query)
            {
                $query->select(Feature\Entity::ENTITY_ID)
                    ->distinct()
                    ->from(Table::FEATURE)
                    ->where(Feature\Entity::NAME, '=', Feature\Constants::EXCLUDE_FROM_CA_BILLING)
                    ->where(Feature\Entity::ENTITY_TYPE, '=', Feature\Constants::MERCHANT);
            })
            ->where($balanceType, '=', Balance\Type::BANKING)
            ->where($balanceAccountType, '=', Balance\AccountType::DIRECT)
            ->whereNotNull($balanceAccountNumber);

        if (!empty($balanceAmount))
        {
            $query->where($balanceCol, '<', $balanceAmount);
        }

        return $query->select($balanceMerchantId, $balanceId . ' AS balance_id', $balanceAccountNumber . ' AS account_number')
            ->distinct()
            ->get();
    }

    // DBA: https://razorpay.atlassian.net/browse/DBOPS-3728
    public function fetchSumQueuedFeeRecoveryPayoutsForMerchant(string $merchantId, string $balanceId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::BALANCE_ID, '=', $balanceId)
            ->where(Entity::STATUS, '=', Status::QUEUED)
            ->where(Entity::PURPOSE, '=', Purpose::RZP_FEES)
            ->selectRaw('SUM(' . Entity::AMOUNT .') AS amount')
            ->first();
    }

    public function fetchNonTerminalPayoutsForMerchant($merchantId, $balanceId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->whereNotIn(Entity::STATUS, Status::$finalStates)
                    ->limit(1)
                    ->get();
    }

}
