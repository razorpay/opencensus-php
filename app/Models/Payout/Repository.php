<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Models\Feature\Constants;
use Illuminate\Database\Query\JoinClause;

use DB;

use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Payout;
use RZP\Models\Contact;
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
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Workflow\Service\StateMap\Entity as WorkflowStateMap;
use RZP\Models\Workflow\Service\EntityMap\Entity as WorkflowEntityMap;

class Repository extends Base\Repository
{
    const QUEUED_PAYOUTS_FETCH_LIMIT = 5000;
    const PENDING_PAYOUTS_FETCH_LIMIT = 5000;
    const BATCH_PAYOUTS_FETCH_LIMIT = 300;
    const SCHEDULED_PAYOUTS_FETCH_LIMIT = 5000;

    protected $entity = 'payout';

    public function saveOrFail($payout, array $options = array())
    {
        $highTPSCompositePayoutFlag = $payout->merchant->isFeatureEnabled(Constants::HIGH_TPS_COMPOSITE_PAYOUT);

        ($highTPSCompositePayoutFlag === true) ? parent::saveOrFailWithoutEsSync($payout, $options) : parent::saveOrFail($payout, $options);
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

    public function fetchMultiple(array $input, string $merchantId)
    {
        $this->setBaseQueryIfApplicable($merchantId);

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

        return parent::fetch($input, $merchantId);
    }

    protected function setBaseQueryIfApplicable(string $merchantId)
    {
        $this->baseQuery = $this->newQueryWithConnection($this->getSlaveConnection());
    }

    public function fetchReversedPayouts(array $ids)
    {
        return $this->newQuery()
                    ->with(['destination', 'fundAccount.account'])
                    ->whereIn(Entity::ID, $ids)
                    ->status(Status::REVERSED)
                    ->get();
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

    public function fetchPayoutsFromCmsRefNumber($cmsRefNumber, $amount, $balanceId)
    {
        $ftaTable           = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn  = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
        $ftaCmsRefNumColumn = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::CMS_REF_NO);

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutModeColumn           = $this->repo->payout->dbColumn(Entity::MODE);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);

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

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutInitiatedAtColumn    = $this->repo->payout->dbColumn(Payout\Entity::INITIATED_AT);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutsMethodColumn        = $this->repo->payout->dbColumn(Entity::MODE);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($ftaCmsRefNumColumn, $cmsRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->where($payoutsMethodColumn, Mode::IFT)
                    ->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime])
                    ->get();
    }

    public function fetchPayoutsFromGatewayRefNumberWithinTimeRangeForIFT(
        $gatewayRefNumber,
        $txnDateTime,
        $txnDateTimeBefore,
        $amount,
        $balanceId)
    {
        $ftaTable           = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn  = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
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
                    ->whereRaw('UPPER(`fund_transfer_attempts`.`gateway_ref_no`) = ?', $gatewayRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->where($ftaModeColumn, Mode::IFT)
                    ->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime])
                    ->get();
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

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsBalanceColumn       = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutInitiatedAtColumn    = $this->repo->payout->dbColumn(Payout\Entity::INITIATED_AT);
        $payoutsAmountColumn        = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutsMethodColumn        = $this->repo->payout->dbColumn(Entity::MODE);
        $payoutsTransactionIdColumn = $this->repo->payout->dbColumn(Entity::TRANSACTION_ID);

        $payoutAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($payoutAttrs)
                    ->join($ftaTable, $payoutsIdColumn, '=', $ftaSourceIdColumn)
                    ->where($payoutsBalanceColumn, $balanceId)
                    ->where($ftaCmsRefNumColumn, $cmsRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->where($payoutsMethodColumn, Mode::IFT)
                    ->whereNull($payoutsTransactionIdColumn)
                    ->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime])
                    ->get();
    }

    public function fetchUnlinkedPayoutsFromGatewayRefNumberWithinTimeRangeForIFT(
        $gatewayRefNumber,
        $txnDateTime,
        $txnDateTimeBefore,
        $amount,
        $balanceId)
    {
        $ftaTable               = $this->repo->fund_transfer_attempt->getTableName();
        $ftaSourceIdColumn      = $this->repo->fund_transfer_attempt->dbColumn(Attempt\Entity::SOURCE_ID);
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
                    ->whereRaw('UPPER(`fund_transfer_attempts`.`gateway_ref_no`) = UPPER(?)', $gatewayRefNumber)
                    ->where($payoutsAmountColumn, $amount)
                    ->where($ftaModeColumn, Mode::IFT)
                    ->whereNull($payoutsTransactionIdColumn)
                    ->whereBetween($payoutInitiatedAtColumn, [$txnDateTimeBefore, $txnDateTime])
                    ->get();
    }

    public function fetchQueuedPayouts(array $merchantIdsWhitelist = [],
                                       array $merchantIdsBlacklist = [],
                                       string $balanceType = Balance\Type::BANKING)
    {
        // select(payouts.*) because if we don't restrict to payouts table columns,
        // collection_item->balance will return the balance field from joined table
        // as opposed to the expected eager-loaded balance entity
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($this->getTableName() . ".*")
                      ->with(['balance', 'merchant', 'merchant.org'])
                      ->status(Status::QUEUED);

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

        $statusColumn      = $this->repo->payout->dbColumn(Entity::STATUS);
        $merchantIdColumn  = $this->repo->payout->dbColumn(Entity::MERCHANT_ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->with(['balance', 'merchant'])
                      ->select($this->getTableName() . ".*")
                      ->where($merchantIdColumn, '=', $merchantId)
                      ->wherein($statusColumn,[Status::QUEUED, Status::ON_HOLD]);

        $this->joinQueryBalance($query);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $query->where($balanceTypeColumn, '=', $balanceType);

        return $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
                     ->get();
    }

    public function fetchScheduledPayouts(string $merchantId)
    {
        $statusColumn       = $this->repo->payout->dbColumn(Entity::STATUS);
        $scheduledAtColumn  = $this->repo->payout->dbColumn(Entity::SCHEDULED_AT);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->with(['balance', 'merchant'])
                    ->whereIn($statusColumn, [Status::SCHEDULED, Status::PENDING])
                    ->whereNotNull($scheduledAtColumn)
                    ->merchantId($merchantId)
                    ->limit(self::SCHEDULED_PAYOUTS_FETCH_LIMIT)
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
    public function getCAMerchantIdsWithAtleastOnePayout(string $channel, int $startTime, int $endTime)
    {
        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);
        $balanceChannelColumn     = $this->repo->balance->dbColumn(Balance\Entity::CHANNEL);

        $payoutInitiatedAtColumn = $this->dbColumn(Entity::INITIATED_AT);
        $payoutsBalanceIdColumn  = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutMerchantIdColumn  = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($payoutMerchantIdColumn)
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::DIRECT)
                    ->where($balanceChannelColumn, '=', $channel)
                    ->whereBetween($payoutInitiatedAtColumn, [$startTime, $endTime])
                    ->distinct()
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function getOnHoldPayoutsWithBeneBankUp(array $beneBanksDownList = [])
    {
        $payoutStatus = $this->dbColumn(Entity::STATUS);

        $payoutIdColumn = $this->dbColumn(Entity::ID);

        $fundAccountId = $this->repo->fund_account->dbColumn(FundAccountEntity::ID);

        $fundAccountIdInPayout = $this->dbColumn(Entity::FUND_ACCOUNT_ID);

        $bankAccountIdInFundAccount = $this->repo->fund_account->dbColumn(FundAccountEntity::ACCOUNT_ID);

        $bankAccountId = $this->repo->bank_account->dbColumn(BankAccountEntity::ID);

        $bankAccountIfscColumn = $this->repo->bank_account->dbColumn(BankAccountEntity::IFSC_CODE);

        $query= $this->newQueryWithConnection($this->getSlaveConnection())
                     ->leftJoin(Table::FUND_ACCOUNT, $fundAccountIdInPayout, '=', $fundAccountId)
                     ->leftJoin(Table::BANK_ACCOUNT, $bankAccountIdInFundAccount, '=', $bankAccountId)
                     ->select($payoutIdColumn)
                     ->where($payoutStatus, '=', Status::ON_HOLD);

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

    public function getMerchantIdsWithAtleastOneOnHoldPayout()
    {
        $onholdAtColumn = $this->dbColumn(Entity::ON_HOLD_AT);
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $statusColumn = $this->dbColumn(Entity::STATUS);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($merchantIdColumn)
                    ->where($statusColumn, '=', Status::ON_HOLD)
                    ->whereNotNull($onholdAtColumn)
                    ->distinct()
                    ->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
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

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($payoutIdColumn)
                    ->where($statusColumn, '=', Status::ON_HOLD)
                    ->whereNotNull($onHoldAtColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($onHoldAtColumn, "<=", strtotime(('-' . ($sla * 60) . ' seconds'), $currentTimeStamp))
                    ->limit($fetchLimit)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function fetchQueuedPayoutsForBalanceId(string $balanceId,
                                                   $offset = 0)
    {
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->with(['balance', 'merchant', 'merchant.org'])
                      ->where($statusColumn, '=', Status::QUEUED)
                      ->where($balanceIdColumn, '=', $balanceId);

        if ($offset !== 0)
        {
            $query->offset($offset);
        }

        return $query->limit(self::QUEUED_PAYOUTS_FETCH_LIMIT)
                     ->get();
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
                        ->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_merchant_id_created_at_index)'))
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
        }

        $this->filterByRoleIds($query, $userRoleId, [$user->getId()]);

        $query->merchantId($merchant->getId());

        // TODO: Update this to handle scale
        // JIRA: https://razorpay.atlassian.net/browse/RX-420
        $query->limit(self::PENDING_PAYOUTS_FETCH_LIMIT);

        $query->with(['balance']);

        $this->joinQueryBalance($query);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $query->where($balanceTypeColumn, '=', $balanceType);

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

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
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

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax,
                         SUM(' . Entity::FEES . ') AS fee')
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNotNull($payoutsInitiatedAtColumn)
                    ->whereNull($payoutsFeeTypeColumn)
                    ->whereBetween($payoutsFailedAtColumn, [$startTime, $endTime])
                    ->where($payoutsStatusColumn, '=', Status::FAILED)
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
        $query->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_merchant_id_created_at_index)'));

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

        $query->where($statusColumn, Status::PENDING)
              ->where($merchantIdColumn, $this->merchant->getId());
    }

    protected function addQueryParamPendingOnMe(BuilderEx $query, array $params)
    {
        $query->from(\DB::raw(Table::PAYOUT.' USE INDEX (payouts_merchant_id_created_at_index)'));

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

        return $this->newQuery()
                    ->select($merchantIdColumn)
                    ->where($statusColumn, '=', Status::BATCH_SUBMITTED)
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

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($payoutsBalanceIdColumn, $payoutStatusColumn, $payoutsIdColumn, $payoutAmountColumn, $payoutsIsPayoutServiceColumn)
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

        return $this->newQueryWithConnection($this->getDataWarehouseConnection())
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

    /**
     * get yesterday's total payout amount and tax count for merchants
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

        return $this->newQueryWithConnection($this->getDataWarehouseConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $payoutsBalanceIdColumn)
                    ->join(Table::MERCHANT, $merchantIdColumn, '=', $payoutsMerchantIdColumn)
                    ->betweenTime($from, $to)
                    ->selectRaw(
                        $payoutsMerchantIdColumn .' as x_merchant_id' . ',' .
                        'COALESCE('. $merchantBillingLabelColumn .',' . $merchantNameColumn .') as x_merchant_display_name'. ',' .
                        'COALESCE('. $merchantWebsiteColumn .',"Not Available") as x_merchant_website,' .
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

    public function fetchMerchantUserDataHavingPendingPayouts()
    {
        /*
         select distinct `merchant_users`.`user_id`, `users`.`name`, `users`.`email`, `merchants`.`name` as `business_name`,
            `payouts`.`merchant_id`, `merchant_users`.`role`, COUNT( payouts.id) AS payout_count, SUM( payouts.amount) AS payout_total
        from `payouts`
        inner join `workflow_entity_map` on `payouts`.`id` = `workflow_entity_map`.`entity_id`
               and `workflow_entity_map`.`entity_type` = ?
        inner join `workflow_state_map` on `workflow_entity_map`.`workflow_id` = `workflow_state_map`.`workflow_id`
            and `workflow_state_map`.`status` = ?
        inner join `merchant_users` on `workflow_state_map`.`merchant_id` = `merchant_users`.`merchant_id`
        inner join `merchants` on `merchant_users`.`merchant_id` = `merchants`.`id`
        inner join `users` on `merchant_users`.`user_id` = `users`.`id`
        where `merchant_users`.`role` = `workflow_state_map`.`actor_type_value`
            and `merchant_users`.`product` = ?
            and `payouts`.`status` = ?
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

        $query = $this->newQuery()
            ->select($this->getTableName() . '.*')
            ->select($userAttrs)
            ->selectRaw('COUNT( payouts.' . Entity::ID . ') AS payout_count,
                           SUM( payouts.' . Entity::AMOUNT . ') AS payout_total')
            ->with(['merchant']);

        //Workflow state map has only two status processed/created
        $this->joinQueryWorkflowServiceEntities($query, [],Status::CREATED);

        $query->whereColumn($merchantUserRoleColumn, '=', $workflowStateMapActorTypeValue);

        $query->join(Table::MERCHANT_USER, $workflowStateMapMerchantId, '=', $merchantUserMerchantIdColumn)
            ->join(Table::MERCHANT, $merchantUserMerchantIdColumn, '=', $merchantIdColumn)
            ->join(Table::USER, $merchantUserUserIdColumn, '=', $userIdColumn)
            ->where($merchantUserProductColumn, Merchant\Balance\Type::BANKING)
            ->where($payoutStatus, Status::PENDING)
            ->groupBy(
                Entity::MERCHANT_ID,
                Merchant\Entity::NAME,
                Merchant\MerchantUser\Entity::USER_ID,
                User\Entity::NAME, User\Entity::EMAIL,
                Merchant\MerchantUser\Entity::ROLE,
                'business_name'
            )
            ->distinct();

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

    public function updatePayout($payoutId, $merchantId, $updates) {
        return $this->newQuery()
            ->where(Entity::ID, $payoutId)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->update($updates);
    }
}
