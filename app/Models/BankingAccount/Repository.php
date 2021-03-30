<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    protected $expands = [
        Entity::BANKING_ACCOUNT_DETAILS,
        Entity::MERCHANT,
        Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS
    ];

    public function getFromBalanceId(string $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->first();
    }

    public function findByAccountNumberAndChannel(string $accountNumber, string $channel)
    {
        return $this->whereAccountNumberAndChannelAre($accountNumber, $channel)
                    ->firstOrFail();
    }

    public function findByMerchantAndAccountNumberPublic(Merchant\Entity $merchant, string $accountNumber)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::ACCOUNT_NUMBER , '=' , $accountNumber)
                    ->first();
    }

    public function findByAccountNumberAndChannelPublic(string $accountNumber, string $channel)
    {
        return $this->whereAccountNumberAndChannelAre($accountNumber, $channel)
                    ->firstOrFailPublic();
    }

    public function whereAccountNumberAndChannelAre($accountNumber, $channel)
    {
        return $this->newQuery()
                    ->where(Entity::ACCOUNT_NUMBER, '=', $accountNumber)
                    ->where(Entity::CHANNEL, '=', $channel);
    }

    /**
     * @param string      $channel
     * @param string|null $bankReference
     *
     * @return Entity
     */
    public function findByBankReferenceAndChannel(string $channel, string $bankReference = null)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_REFERENCE_NUMBER, '=', $bankReference)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->first();
    }

    public function getBankingAccountOfMerchant(Merchant\Entity $merchant, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->first();
    }

    public function getLatestInsertedBankingAccountEntity(string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->whereNotNull(Entity::BANK_REFERENCE_NUMBER)
                    ->latest(Entity::CREATED_AT)
                    ->first();
    }

    public function getBankingAccountsWithBalance($merchantId)
    {
        return $this->newQuery()
                    ->with(['balance'])
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }

    public function getBankingAccountByMerchantIdAndChannel($merchantId, string $channel)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn            = $this->repo->balance->dbColumn(Entity::ID);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $bankingAccountAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($bankingAccountAttrs)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($balanceAccountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->where($channelColumn, '=', $channel)
                    ->first();
    }

    public function getMerchantIdsByChannel($channel, $limit)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn                = $this->repo->balance->dbColumn(Entity::ID);
        $accountTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $bankingAccountAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($bankingAccountAttrs)
                    ->where($channelColumn, '=', $channel)
                    ->where(Entity::STATUS, '=', Status::ACTIVATED)
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($accountTypeColumn, '=', Merchant\Balance\AccountType::DIRECT)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->oldest(Entity::BALANCE_LAST_FETCHED_AT)
                    ->limit($limit)
                    ->pluck($merchantIdColumn);
    }

    public function fetchByMerchantIdAndAccountType(string $merchantId, string $accountType)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn                = $this->repo->balance->dbColumn(Entity::ID);
        $balanceAccountTypeColumn       = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn              = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        return $this->newQuery()
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', $accountType)
                    ->get();
    }

    public function addQueryParamReviewerId($query, $params)
    {
        AdminEntity::verifyIdAndStripSign($params[Entity::REVIEWER_ID]);

        return $query->whereExists(function ($q) use ($params) {
            $q->select('admin_id')
                ->from(Table::ADMIN_AUDIT_MAP)
                ->where('admin_id', '=', $params[Entity::REVIEWER_ID])
                ->where(Entity::AUDITOR_TYPE,'=','reviewer')
                ->where('entity_type','=','banking_account')
                ->whereRaw(Table::BANKING_ACCOUNT.'.'.Entity::ID.' = '.Table::ADMIN_AUDIT_MAP.'.'.Entity::ENTITY_ID);
        });
    }

    public function addQueryParamSalesPocId($query, $params)
    {
        AdminEntity::verifyIdAndStripSign($params[Entity::SALES_POC_ID]);

        return $query->whereExists(function ($q) use ($params) {
            $q->select('admin_id')
                ->from(Table::ADMIN_AUDIT_MAP)
                ->where('admin_id', '=', $params[Entity::SALES_POC_ID])
                ->where(Entity::AUDITOR_TYPE,'=','spoc')
                ->where('entity_type','=','banking_account')
                ->whereRaw(Table::BANKING_ACCOUNT.'.'.Entity::ID.' = '.Table::ADMIN_AUDIT_MAP.'.'.Entity::ENTITY_ID);
        });
    }

    public function addQueryParamAssigneeTeam($query, $params)
    {
        $assigneeTeamColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::ASSIGNEE_TEAM);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive exact match for merchant email
        $assigneeTeam = $params[ActivationDetail\Entity::ASSIGNEE_TEAM];

        $query->where($assigneeTeamColumn, '=', $assigneeTeam);
    }

    /**
     * Filter out Balance Id for balances where gateway balance has updated in last 24 hours
     *
     * @param array $balanceIdList
     *
     * @return mixed
     */
    public function getBalanceIdsWhereGatewayBalanceUpdatedRecently(array $balanceIdList)
    {
        $statusColumn    = $this->dbColumn(Entity::STATUS);
        $balanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $updatedAtColumn = $this->dbColumn(Entity::UPDATED_AT);

        $oneDayEarlierTimeStamp = Carbon::now(Timezone::IST)->subHours(24)->getTimestamp();

        return $this->newQuery()
                    ->select($balanceIdColumn)
                    ->whereIn($balanceIdColumn, $balanceIdList)
                    ->where($updatedAtColumn, '>=', $oneDayEarlierTimeStamp)
                    ->where($statusColumn, '=', Status::ACTIVATED)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::BALANCE_ID)
                    ->toArray();
    }

    protected function joinQueryMerchantDetail(Base\BuilderEx $query)
    {
        $merchantDetailTable = $this->repo->merchant_detail->getTableName();

        if ($query->hasJoin($merchantDetailTable) === true)
        {
            return;
        }

        $merchantIdColumn = $this->repo->merchant_detail->dbColumn(Merchant\Detail\Entity::MERCHANT_ID);

        $bankingAccountMerchantIdColumn = $this->repo->banking_account->dbColumn(Entity::MERCHANT_ID);

        $query->join($merchantDetailTable, $bankingAccountMerchantIdColumn, '=', $merchantIdColumn);
    }

    protected function joinQueryMerchant(Base\BuilderEx $query)
    {
        $merchantTable = $this->repo->merchant->getTableName();

        if ($query->hasJoin($merchantTable) === true)
        {
            return;
        }

        $merchantIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $bankingAccountMerchantIdColumn = $this->repo->banking_account->dbColumn(Entity::MERCHANT_ID);

        $query->join($merchantTable, $bankingAccountMerchantIdColumn, '=', $merchantIdColumn);
    }

    protected function joinQueryActivationDetail(Base\BuilderEx $query)
    {
        $activationDetailTable = $this->repo->banking_account_activation_detail->getTableName();

        if ($query->hasJoin($activationDetailTable) === true)
        {
            return;
        }

        $bankingAccountIdForeignColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BANKING_ACCOUNT_ID);

        $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);

        $query->join($activationDetailTable, $bankingAccountIdColumn, '=', $bankingAccountIdForeignColumn);
    }

    public function addQueryParamMerchantBusinessName(Base\BuilderEx $query, array $params)
    {
        $this->joinQueryMerchantDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        $merchantBusinessNameColumn = $this->repo->merchant_detail->dbColumn(Merchant\Detail\Entity::BUSINESS_NAME);

        $merchantBusinessname = mb_strtolower($params[Entity::MERCHANT_BUSINESS_NAME]);

        // case insensitive partial match for merchant name
        $query->whereRaw("LOWER(".$merchantBusinessNameColumn.") LIKE '%".$merchantBusinessname."%'");
    }

    public function addQueryParamMerchantEmail(Base\BuilderEx $query, array $params)
    {
        $merchantEmailColumn = $this->repo->merchant->dbColumn(Merchant\Entity::EMAIL);

        $this->joinQueryMerchant($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive exact match for merchant email
        $email = mb_strtolower($params[Entity::MERCHANT_EMAIL]);

        $query->where($merchantEmailColumn, '=', $email);
    }

    protected function joinMerchantPromotions(Base\BuilderEx $query)
    {
        $merchantPromotionsTable = $this->repo->merchant_promotion->getTableName();
        $promotionsTable = $this->repo->promotion->getTableName();

        $bankingAccountMerchantIdColumn = $this->repo->banking_account->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn = $this->repo->merchant_promotion->dbColumn(Merchant\Promotion\Entity::MERCHANT_ID);
        $merchantPromotionIdColumn = $this->repo->merchant_promotion->dbColumn(Merchant\Promotion\Entity::PROMOTION_ID);
        $promotionIdColumn = $this->repo->promotion->dbColumn(\RZP\Models\Promotion\Entity::ID);

        $query->join($merchantPromotionsTable, $bankingAccountMerchantIdColumn, '=', $merchantIdColumn);
        $query->join($promotionsTable, $merchantPromotionIdColumn, '=', $promotionIdColumn);
    }

    public function addQueryParamSource(Base\BuilderEx $query, array $params)
    {
        $this->joinMerchantPromotions($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        $source = $params[Entity::SOURCE];
        $promotionNameColumn = $this->repo->promotion->dbColumn(\RZP\Models\Promotion\Entity::NAME);

        $query->where($promotionNameColumn, '=', $source);
    }

    public function addQueryParamMerchantPocCity(Base\BuilderEx $query, array $params)
    {
        $merchantCityColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::MERCHANT_CITY);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive exact match for merchant email
        $merchantCity = $params[Entity::MERCHANT_POC_CITY];

        // Temporarily the Documentation process (in terms of delivering)
        // is different for Bangalore and Non-Bangalore. Hence, this temporary provision
        // to allow not check.
        // In future, once processes get streamlined, this may be unnecessary.
        if ($merchantCity[0] === '!')
        {
            $query->where($merchantCityColumn, '!=', substr($merchantCity, 1));
        }
        else
        {
            $query->where($merchantCityColumn, '=', $merchantCity);
        }
    }

    public function addQueryParamBankAccountType(Base\BuilderEx $query, array $params)
    {
        $bankAccountTypeColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::ACCOUNT_TYPE);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive exact match for merchant email
        $bankAccountType = $params[Entity::BANK_ACCOUNT_TYPE];

        $query->where($bankAccountTypeColumn, '=', $bankAccountType);
    }

    public function addQueryParamIsDocumentsWalkthroughComplete(Base\BuilderEx $query, array $params)
    {
        $isDocWalkthroughCompleteColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive exact match for merchant email
        $isDocWalkthroughComplete = $params[Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE];

        $query->where($isDocWalkthroughCompleteColumn, '=', $isDocWalkthroughComplete);
    }

    /**
     *
     * select distinct `merchant_id` from `banking_accounts`
     *         where `channel` = ? and
     *        `account_type` = ? and
     *        `status` = ? and
     *        `merchant_id` in (?)
     *         order by `merchant_id` asc
     *
     * @param array  $merchantIds
     * @param string $channel
     * @param string $accountType
     *
     * @return array
     */
    public function fetchActiveCurrentAccountForMerchantIds(array $merchantIds, string $channel, string $accountType): array
    {
        return $this->newQuery()
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->where(Entity::ACCOUNT_TYPE, $accountType)
                    ->where(Entity::STATUS, 'activated')
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->distinct()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();

    }

    public function fetchMerchantBankingAccounts(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get([Entity::ACCOUNT_NUMBER, Entity::ACCOUNT_TYPE, Entity::CHANNEL, Entity::STATUS])
                    ->toArray();
    }

    public function fetchByAccountNumberAndChannel(string $accountNumber, string $channel)
    {
        return $this->whereAccountNumberAndChannelAre($accountNumber, $channel)
                    ->first();
    }

    public function getCAOnboardCohortList(int $startTime, int $endTime)
    {
        $balanceIdColumn                    = $this->repo->balance->dbColumn(Entity::ID);
        $balanceCreatedColumn               = $this->repo->balance->dbColumn(Entity::CREATED_AT);
        $bankingAccountsBalanceIdColumn     = $this->dbColumn(Entity::BALANCE_ID);
        $activationStatus                   = $this->dbColumn(Entity::STATUS);
        $accountTypeColumn                  = $this->dbColumn(Entity::ACCOUNT_TYPE);

        $selectAttr                 = [
            $this->dbColumn(Entity::MERCHANT_ID),
        ];

        return $this->newQuery()
            ->select($selectAttr)
            ->join(Table::BALANCE, $balanceIdColumn, '=', $bankingAccountsBalanceIdColumn)
            ->where($accountTypeColumn, '=', AccountType::CURRENT)
            ->where($activationStatus, '=', Status::ACTIVATED)
            ->whereBetween($balanceCreatedColumn, [$startTime, $endTime])
            ->groupBy(Entity::MERCHANT_ID)
            ->get();
    }

}
