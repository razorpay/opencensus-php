<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;

use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;
use RZP\Models\Workflow;
use RZP\Models\Admin\Org;
use RZP\Constants\Timezone;
use RZP\Models\FundAccount;
use RZP\Models\FeeRecovery;
use RZP\Models\Workflow\Step;
use RZP\Constants\Entity as E;
use RZP\Models\Workflow\Action;
use RZP\Models\User\BankingRole;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Workflow\Action\Checker;

class Repository extends Base\Repository
{
    const QUEUED_PAYOUTS_FETCH_LIMIT = 5000;
    const PENDING_PAYOUTS_FETCH_LIMIT = 5000;
    const BATCH_PAYOUTS_FETCH_LIMIT = 300;
    const SCHEDULED_PAYOUTS_FETCH_LIMIT = 5000;

    protected $entity = 'payout';

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

    public function fetchQueuedPayouts(array $merchantIdsWhitelist = [],
                                       array $merchantIdsBlacklist = [],
                                       string $balanceType = Balance\Type::BANKING)
    {
        // select(payouts.*) because if we don't restrict to payouts table columns,
        // collection_item->balance will return the balance field from joined table
        // as opposed to the expected eager-loaded balance entity
        $query = $this->newQuery()
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

    public function fetchScheduledPayouts(string $merchantId)
    {
        $statusColumn       = $this->repo->payout->dbColumn(Entity::STATUS);
        $scheduledAtColumn  = $this->repo->payout->dbColumn(Entity::SCHEDULED_AT);

        return $this->newQuery()
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
        $queuedAtColumn = $this->dbColumn(Entity::QUEUED_AT);
        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);

        return $this->newQuery()
                    ->select($balanceIdColumn)
                    ->whereNotNull($queuedAtColumn)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::BALANCE_ID)
                    ->toArray();
    }

    public function fetchQueuedPayoutsForBalanceId(string $balanceId,
                                                   $offset = 0)
    {
        $statusColumn = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);

        $query = $this->newQuery()
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

        return $this->newQuery()
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
     * @param User\Entity     $user
     * @param Merchant\Entity $merchant
     * @param string          $balanceType
     *
     * @return Base\Collection
     */
    public function fetchPayoutsPendingOnUserRole(User\Entity $user,
                                                  Merchant\Entity $merchant,
                                                  string $balanceType = Balance\Type::BANKING): Base\Collection
    {
        // select(payouts.*) because if we don't restrict to payouts table columns,
        // collection_item->balance will return the balance field from joined table
        // as opposed to the expected eager-loaded balance entity
        /** @var BuilderEx $query */
        $query = $this->newQuery()
                      ->select($this->getTableName() . ".*");

        // If the entity is a user(which implies the product is banking),
        // then the role id for that user for the merchant in context
        // will have to be fetched from the merchant_users table.
        // This is because the role_map table doesn't have any merchant context.
        $userRoleId = (new User\Core())->getUserRoleIdInMerchantForBanking($user->getId());

        $this->filterByRoleIds($query, $userRoleId, $user->getId());

        $query->merchantId($merchant->getId());

        // TODO: Update this to handle scale
        // JIRA: https://razorpay.atlassian.net/browse/RX-420
        $query->limit(self::PENDING_PAYOUTS_FETCH_LIMIT);

        $query->with(['balance']);

        $this->joinQueryBalance($query);

        $balanceTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $query->where($balanceTypeColumn, '=', $balanceType);

        return $query->get();
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

        return $this->newQuery()
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax,
                         SUM(' . Entity::FEES . ') AS fee')
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
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

        return $this->newQuery()
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax,
                         SUM(' . Entity::FEES . ') AS fee')
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNotNull($payoutsInitiatedAtColumn)
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
        $payoutsBalanceIdColumn     = $this->dbColumn(Entity::BALANCE_ID);
        $payoutsInitiatedAtColumn   = $this->dbColumn(Entity::INITIATED_AT);

        return $this->newQuery()
                    ->select($payoutsIdColumn, $payoutsFeesColumn)
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNotNull($payoutsInitiatedAtColumn)
                    ->whereBetween($payoutsInitiatedAtColumn, [$start, $end])
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
        $payoutsFailedAtColumn      = $this->dbColumn(Entity::FAILED_AT);
        $payoutsBalanceIdColumn     = $this->dbColumn(Entity::BALANCE_ID);
        $payoutsInitiatedAtColumn   = $this->dbColumn(Entity::INITIATED_AT);

        return $this->newQuery()
                    ->select($payoutsIdColumn, $payoutsFeesColumn)
                    ->merchantId($merchantId)
                    ->where($payoutsBalanceIdColumn, $balanceId)
                    ->whereNotNull($payoutsInitiatedAtColumn)
                    ->whereBetween($payoutsFailedAtColumn, [$start, $end])
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

    protected function addQueryParamPendingOnRoles(BuilderEx $query, array $params)
    {
        $pendingOnRoles = $params[Entity::PENDING_ON_ROLES];

        $pendingRoleIds = $this->repo->role->fetchIdsByOrgIdNames(
            Org\Entity::RAZORPAY_ORG_ID,
            BankingRole::getNamesForWorkflowRoles($pendingOnRoles));

        $this->filterByRoleIds($query, $pendingRoleIds->pluck('id')->toArray());
    }

    protected function addQueryParamPendingOnMe(BuilderEx $query, array $params)
    {
        $pendingOnMe = (bool) ($params[Entity::PENDING_ON_ME] ?? false);

        if ($pendingOnMe === false)
        {
            return;
        }

        // If the entity is a user(which implies the product is banking),
        // then the role id for that user for the merchant in context
        // will have to be fetched from the merchant_users table.
        // This is because the role_map table doesn't have any merchant context.
        $userRoleId = (new User\Core())->getUserRoleIdInMerchantForBanking($this->auth->getUser()->getId());

        $this->filterByRoleIds($query, $userRoleId);
    }

    protected function filterByRoleIds(BuilderEx $query, array $roleIds, string $userId = null)
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

    protected function filterCompletedCheckerId(BuilderEx $query, string $checkerId)
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
                         ->where($actionCheckerCheckerId, '=', $checkerId);

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


        return $this->newQuery()
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

        return $this->newQuery()
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

        $payoutsIdColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutAmountColumn         = $this->repo->payout->dbColumn(Entity::AMOUNT);
        $payoutStatusColumn         = $this->repo->payout->dbColumn(Entity::STATUS);
        $payoutsBalanceIdColumn     = $this->repo->payout->dbColumn(Entity::BALANCE_ID);
        $payoutsScheduledAtColumn   = $this->repo->payout->dbColumn(Entity::SCHEDULED_AT);

        $query = $this->newQuery()
                      ->select($payoutsBalanceIdColumn, $payoutStatusColumn, $payoutsIdColumn, $payoutAmountColumn)
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
}
