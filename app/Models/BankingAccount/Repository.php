<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use Illuminate\Support\Facades\DB;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\BankingAccount\State as BankingAccountState;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\Base\PublicEntity;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    public function __construct()
    {
        parent::__construct();

        $this->setMerchantIdRequiredForMultipleFetch(false);
    }

    protected $expands = [
        Entity::BANKING_ACCOUNT_DETAILS,
        Entity::MERCHANT,
        Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS
    ];

    public function getActivatedBankingAccountFromBalanceId(string $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->where(Entity::STATUS, '!=', Status::ARCHIVED)
                    ->first();
    }

    public function getFromBalanceId(string $balanceId)
    {
        return $this->newQuery()
            ->where(Entity::BALANCE_ID, '=', $balanceId)
            ->first();
    }

    public function getFromBalanceIdOrFail(string $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::BALANCE_ID, '=', $balanceId)
                    ->firstOrFail();
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
                    ->where(Entity::STATUS, '<>', Status::TERMINATED)
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

    public function getActiveBankingAccountByMerchantIdAndChannel($merchantId, string $channel)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $channelColumn                 = $this->dbColumn(Entity::CHANNEL);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);
        $statusColumn                  = $this->dbColumn(Entity::STATUS);

        $balanceIdColumn            = $this->repo->balance->dbColumn(Entity::ID);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        $bankingAccountAttrs = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($bankingAccountAttrs)
                    ->where($statusColumn, '=', Status::ACTIVATED)
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

    public function fetchActiveBankingAccountsByMerchantIdAndAccountType(string $merchantId, string $accountType)
    {
        $bankingAccountBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $bankingAccountChannelColumn   = $this->dbColumn(Entity::CHANNEL);
        $bankingAccountStatusColumn    = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn              = $this->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn          = $this->repo->balance->dbColumn(Entity::ID);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Merchant\Balance\Entity::ACCOUNT_TYPE);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Merchant\Balance\Entity::TYPE);

        return $this->newQuery()
                    ->select($bankingAccountChannelColumn)
                    ->where($bankingAccountStatusColumn, '=', Status::ACTIVATED)
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->join(Table::BALANCE, $bankingAccountBalanceIdColumn, '=', $balanceIdColumn)
                    ->where($balanceTypeColumn, '=', Merchant\Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', $accountType)
                    ->get();
    }

    public function fetchBankingAccountsWithMatchingMerchantName(Merchant\Entity $merchant, string $bankingAccountId, string $channel = Channel::RBL, string $accountType = AccountType::CURRENT)
    {
        $merchantNameColumn = $this->repo->merchant->dbColumn(Merchant\Entity::NAME);
        $merchantIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $bankingAccountMerchantIdColumn = $this->repo->banking_account->dbColumn(Entity::MERCHANT_ID);
        $bankingAccountChannelColumn = $this->repo->banking_account->dbColumn(Entity::CHANNEL);
        $bankingAccountAccountTypeColumn = $this->repo->banking_account->dbColumn(Entity::ACCOUNT_TYPE);
        $bankingAccountStatusColumn = $this->repo->banking_account->dbColumn(Entity::STATUS);
        $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);

        $merchantName = preg_replace('/[^A-Z -]/', '', strtoupper($merchant->getName()));

        $query = $this->newQuery()
                    ->where($bankingAccountIdColumn, '!=', $bankingAccountId)
                    ->where($bankingAccountChannelColumn, '=', $channel)
                    ->where($bankingAccountAccountTypeColumn, '=', $accountType)
                    ->where($bankingAccountStatusColumn, '!=', Status::CREATED)
                    ->join(Table::MERCHANT, $merchantIdColumn, '=', $bankingAccountMerchantIdColumn)
                    ->whereRaw("UPPER({$merchantNameColumn}) LIKE '%". $merchantName."%'");

        return $query->first();
    }

    public function addQueryParamStatus($query, $params)
    {
        $status = $params[Entity::STATUS];

        $statusColumn = $this->repo->banking_account->dbColumn(Entity::STATUS);

        if (is_array($status) === true)
        {
            $query->whereIn($statusColumn, $status);
        }
        else if (is_string($status) === true)
        {
            $query->where($statusColumn, '=', $status);
        }
    }

    public function addQueryParamExcludeStatus($query, $params)
    {
        $excludeStatus = $params[Entity::EXCLUDE_STATUS];

        $statusColumn = $this->repo->banking_account->dbColumn(Entity::STATUS);

        if (is_array($excludeStatus) === true)
        {
            $query->whereNotIn($statusColumn, $excludeStatus);
        }
        else if (is_string($excludeStatus) === true)
        {
            $query->where($statusColumn, '<>', $excludeStatus);
        }
    }

    public function addQueryParamFilterMerchants($query, $params)
    {
        $merchantId = $this->repo->banking_account->dbColumn(PublicEntity::MERCHANT_ID);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $filterMerchantIds = $params[Entity::FILTER_MERCHANTS];

        $query->whereIn($merchantId, $filterMerchantIds);
    }

    public function addQueryParamBankPocUserId($query, $params)
    {
        $bankPocUserId = $this->repo->banking_account_activation_detail->dbColumn(Activation\Detail\Entity::BANK_POC_USER_ID);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $filterBankPocUserId = $params[Entity::BANK_POC_USER_ID];

        $query->where($bankPocUserId, $filterBankPocUserId);
    }

    public function addQueryParamActivationAccountType($query, $params)
    {
        $bankingAccountStateStatusColumn = $this->repo->banking_account_activation_detail->dbColumn(Activation\Detail\Entity::ACCOUNT_TYPE);
        $filterActivationAccountType = $params[Constants::ACTIVATION_ACCOUNT_TYPE];

        $this->joinQueryActivationDetail($query);
        $query->select($this->dbColumn('*'));

        return $query->where($bankingAccountStateStatusColumn, $filterActivationAccountType);
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

    public function addQueryParamOpsMxPOCId($query, $params)
    {
        AdminEntity::verifyIdAndStripSign($params[Entity::OPS_MX_POC_ID]);

        return $query->whereExists(function ($q) use ($params) {
            $q->select('admin_id')
                ->from(Table::ADMIN_AUDIT_MAP)
                ->where('admin_id', '=', $params[Entity::OPS_MX_POC_ID])
                ->where(Entity::AUDITOR_TYPE, '=', Entity::OPS_MX_POC)
                ->where('entity_type', '=', 'banking_account')
                ->whereRaw(Table::BANKING_ACCOUNT.'.'.Entity::ID.' = '.Table::ADMIN_AUDIT_MAP.'.'.Entity::ENTITY_ID);
        });
    }

    /**
     * Get all banking accounts where either (`admin_id` is reviewer and `assignee_team` is ops)
     * or (`admin_id` is spoc and `assignee_team` is sales)
     */
    public function addQueryParamPendingOn($query, $params)
    {
        AdminEntity::verifyIdAndStripSign($params[Entity::PENDING_ON]);

        return $query->whereExists(function ($q) use ($params) {
            $activationDetailTable = $this->repo->banking_account_activation_detail->getTableName();
            $bankingAccountIdForeignColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BANKING_ACCOUNT_ID);
            $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);

            $q->select('*')
                ->from(Table::ADMIN_AUDIT_MAP)
                ->join($activationDetailTable, $bankingAccountIdColumn, '=', $bankingAccountIdForeignColumn)
                ->where('admin_id', '=', $params[Entity::PENDING_ON])
                ->where(Table::BANKING_ACCOUNT.'.'.Entity::STATUS, '!=', Status::ARCHIVED)
                ->where('entity_type','=','banking_account')
                ->whereRaw("((".Table::ADMIN_AUDIT_MAP.'.'.Entity::AUDITOR_TYPE." = 'spoc' AND ".Table::BANKING_ACCOUNT_ACTIVATION_DETAIL.'.'.Entity::ASSIGNEE_TEAM." = 'sales' ) OR ( ".Table::ADMIN_AUDIT_MAP.'.'.Entity::AUDITOR_TYPE." = 'reviewer' AND ".Table::BANKING_ACCOUNT_ACTIVATION_DETAIL.'.'.Entity::ASSIGNEE_TEAM." like '%ops' ))")
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

    /**
     * Filter to search whether lead is green_channel or not
     */
    public function addQueryParamIsGreenChannel(Base\BuilderEx $query, $params)
    {
        $greenChannel = $params[BankLms\Constants::IS_GREEN_CHANNEL];

        if ($greenChannel === 'yes') {
            $greenChannel = 'true';
        } else {
            $greenChannel = 'false';
        }

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('json_unquote(json_extract(additional_details, \'$."green_channel"\')) = \''.$greenChannel.'\'');
    }

    /**
     * Filter to search whether lead is revived_lead or not
     */
    public function addQueryParamRevivedLead(Base\BuilderEx $query, $params)
    {
        $revivedLead = $params[ActivationDetail\Entity::REVIVED_LEAD];

        if ($revivedLead === 'yes') {
            $revivedLead = 'true';
        } else {
            $revivedLead = 'false';
        }

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('json_unquote(json_extract(additional_details, \'$."revived_lead"\')) = \''.$revivedLead.'\'');
    }

    /**
     * Filter to search whether lead is feet on street or not
     */
    public function addQueryParamFeetOnStreet(Base\BuilderEx $query, $params)
    {
        $feetOnStreet = $params[BankLms\Constants::FEET_ON_STREET];

        if ($feetOnStreet === 'yes') {
            $feetOnStreet = 'true';
        } else {
            $feetOnStreet = 'false';
        }

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('json_unquote(json_extract(additional_details, \'$."feet_on_street"\')) = \''.$feetOnStreet.'\'');
    }

    /**
     * Filter to search when lead was sent to bank - start date
     * Sent from Partner Bank LMS
     * Defined here becuase it is used by the Download MIS
     */
    public function addQueryParamLeadReceivedFromDate($query, $params)
    {
        return $query->whereExists(function ($q) use ($params) {

            $filterFromDate = $params[BankLms\Constants::LEAD_RECEIVED_FROM_DATE];
            $filterToDate = $params[BankLms\Constants::LEAD_RECEIVED_TO_DATE];

            $q->selectRaw('*')
            ->from(function ($q2) {

                $bankingAccountStateTable = $this->repo->banking_account_state->getTableName();
                $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);
                $bankingAccountIdForeignColumn = $this->repo->banking_account_state->dbColumn(State\Entity::BANKING_ACCOUNT_ID);
                $bankingAccountStateStatusColumn = $this->repo->banking_account_state->dbColumn(State\Entity::STATUS);

                $q2->from($bankingAccountStateTable)
                    ->whereRaw($bankingAccountIdColumn.' = '.$bankingAccountIdForeignColumn)
                    ->where($bankingAccountStateStatusColumn, '=', Status::INITIATED)
                    ->whereRaw('( `sub_status` IS NULL or `sub_status` = \'none\' )')
                    ->latest(State\Entity::CREATED_AT)
                    ->limit(1);

            })
            ->where(State\Entity::CREATED_AT, '>=', $filterFromDate)
            ->where(State\Entity::CREATED_AT, '<=', $filterToDate);
        });
    }

    /**
     * Filter to search when lead was sent to bank - end date
     *
     * This is a dummy code block, the end date filter is handled above in `addQueryParamLeadReceivedFromDate`
     * The validator will ensure that we are getting both fields
     */
    public function addQueryParamLeadReceivedToDate($query, $params)
    {
        return $query;
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

        // this value will be coming from Partner LMS
        if ($assigneeTeam == 'rzp')
        {
            $assigneeTeam = [ActivationDetail\Entity::OPS, ActivationDetail\Entity::SALES, Entity::OPS_MX_POC];
        }
        else if ($assigneeTeam === ActivationDetail\Entity::BANK or $assigneeTeam === ActivationDetail\Entity::OPS)
        {
            $assigneeTeam = [$assigneeTeam, ActivationDetail\Entity::BANK_OPS];
        }
        else
        {
            $assigneeTeam = [$assigneeTeam];
        }

        $query->whereIn($assigneeTeamColumn, $assigneeTeam);
    }

    public function addQueryParamRmName($query, $params)
    {
        $rmNameColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::RM_NAME);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $rmName = mb_strtolower($params[ActivationDetail\Entity::RM_NAME]);

        // case insensitive partial match for rm name
        $query->whereRaw("LOWER(".$rmNameColumn.") LIKE '%".$rmName."%'");
    }

    public function addQueryParamBranchCode($query, $params)
    {
        $branchCodeColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BRANCH_CODE);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $branchCode = $params[ActivationDetail\Entity::BRANCH_CODE];

        $query->where($branchCodeColumn, '=', $branchCode);
    }

    public function addQueryParamApiOnboardingFtnr($query, $params)
    {
        $apiOnboardingFtnrColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::API_ONBOARDING_FTNR);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $apiOnboardingFtnr = $params[ActivationDetail\Entity::API_ONBOARDING_FTNR];

        $query->where($apiOnboardingFtnrColumn, '=', $apiOnboardingFtnr);
    }

    public function addQueryParamAccountOpeningFtnr($query, $params)
    {
        $accountOpeningFtnrColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::ACCOUNT_OPENING_FTNR);

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $accountOpeningFtnr = $params[ActivationDetail\Entity::ACCOUNT_OPENING_FTNR];

        $query->where($accountOpeningFtnrColumn, '=', $accountOpeningFtnr);
    }

    /**
     * Filter to search due leads using bank due date in banking_account_activation_detail->rbl_activation_details
     */
    public function addQueryParamDueOn(Base\BuilderEx $query, $params)
    {
        $dueDate = $params[BankLms\Constants::DUE_ON];

        $dueDate = (int)$dueDate;

        $date = new Carbon($dueDate);
        $start = $date->copy()->startOfDay()->timestamp;
        $end = $date->copy()->endOfDay()->timestamp;

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('json_unquote(json_extract(rbl_activation_details, \'$."bank_due_date"\')) BETWEEN '.$start.' AND '.$end);
    }

    /**
     * Filter to search overdue leads using due date
     *
     * If is_overdue is 1, bank_due_date should be less than today start
     * If is_overdue is 0, bank_due_date should be more than today end
     */
    public function addQueryParamIsOverdue(Base\BuilderEx $query, $params)
    {
        $overdue = $params[BankLms\Constants::IS_OVERDUE];

        $date = new Carbon();

        // default for is_overdue = 1
        $start = 941627769; // 1999-11-03
        $end = (new Carbon())->startOf('day')->timestamp;

        if ($overdue === '0') {
            $start = (new Carbon())->startOf('day')->timestamp;
            $end = 16750953000; // 2500-10-26
        }

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('json_unquote(json_extract(rbl_activation_details, \'$."bank_due_date"\')) BETWEEN '.$start.' AND '.$end);
    }

    public function addQueryParamApplicationType($query, $params)
    {
        $applicationTypeColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::APPLICATION_TYPE);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrupted data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case-insensitive exact match for merchant email
        $applicationType = $params[ActivationDetail\Entity::APPLICATION_TYPE];

        $query->where($applicationTypeColumn, '=', $applicationType);
    }

    public function addQueryParamFromSlotBooked($query, $params)
    {
        $slotBookingColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BOOKING_DATE_AND_TIME);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        $fromSlotBookedTime = $params[Entity::FROM_SLOT_BOOKED];

        $query->where($slotBookingColumn, '>', $fromSlotBookedTime);
    }

    public function addQueryParamToSlotBooked($query, $params)
    {
        $slotBookingColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BOOKING_DATE_AND_TIME);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        $toSlotBookedTime = $params[Entity::TO_SLOT_BOOKED];

        $query->where($slotBookingColumn, '<', $toSlotBookedTime);
    }

    public function addQueryParamSortSlotBooked($query, $params)
    {
        $slotBookingColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BOOKING_DATE_AND_TIME);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        $sortSlotBooked = $params[Entity::SORT_SLOT_BOOKED];

        if ($sortSlotBooked === 'asc')
        {
            $query->orderBy($slotBookingColumn, 'asc');
        }
        if ($sortSlotBooked === 'desc')
        {
            $query->orderBy($slotBookingColumn, 'desc');
        }
    }

    public function addQueryParamFilterSlotBooked($query, $params)
    {
        $slotBookingColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BOOKING_DATE_AND_TIME);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        $filterSlotBooked = $params[Entity::FILTER_SLOT_BOOKED];

        if ($filterSlotBooked === '1')
        {
            $query->where($slotBookingColumn, '!=', null);
        }
        if ($filterSlotBooked === '0')
        {
            $query->where($slotBookingColumn, '=', null);
        }
    }

    public function addQueryParamFromFollowUpDate($query, $params)
    {
        $followUpDateColumn = $this->repo->banking_account_call_log->dbColumn(\RZP\Models\BankingAccount\Activation\CallLog\Entity::FOLLOW_UP_DATE_AND_TIME);

        $this->joinQueryActivationDetailCallLog($query);

        // selecting banking_accounts columns and follow update so that
        // filter and sorting can work on that
        $query->select($this->dbColumn('*'), DB::raw('max(banking_account_call_log.follow_up_date_and_time) as latest_follow_up_date'));

        $fromFollowUpDate = $params[Entity::FROM_FOLLOW_UP_DATE];

        $query->where($followUpDateColumn, '>', $fromFollowUpDate);
    }

    public function addQueryParamToFollowUpDate($query, $params)
    {
        $followUpDateColumn = $this->repo->banking_account_call_log->dbColumn(\RZP\Models\BankingAccount\Activation\CallLog\Entity::FOLLOW_UP_DATE_AND_TIME);

        $this->joinQueryActivationDetailCallLog($query);

        // selecting banking_accounts columns and follow update so that
        // filter and sorting can work on that
        $query->select($this->dbColumn('*'), DB::raw('max(banking_account_call_log.follow_up_date_and_time) as latest_follow_up_date'));

        $toFollowUpDate = $params[Entity::TO_FOLLOW_UP_DATE];

        $query->where($followUpDateColumn, '<', $toFollowUpDate);
    }

    public function addQueryParamSortFollowUpDate($query, $params)
    {
        $followUpDateColumn = 'latest_follow_up_date';

        $this->joinQueryActivationDetailCallLog($query);

        // selecting banking_accounts columns and follow update so that
        // filter and sorting can work on that
        $query->select($this->dbColumn('*'), DB::raw('max(banking_account_call_log.follow_up_date_and_time) as latest_follow_up_date'));

        $sortFollowUpDate = $params[Entity::SORT_FOLLOW_UP_DATE];

        if ($sortFollowUpDate === 'asc')
        {
            $query->orderBy($followUpDateColumn, 'asc');
        }
        if ($sortFollowUpDate === 'desc')
        {
            $query->orderBy($followUpDateColumn, 'desc');
        }
    }

    public function addQueryParamSortSentToBankDate(Base\BuilderEx $query, array $params)
    {
        $sentToBankDate = 'sent_to_bank_date';
        $sortOrder = $params[BankLms\Constants::SORT_SENT_TO_BANK_DATE];

        $bankingAccountState = $this->repo->banking_account_state->getTableName();
        $bankingAccountId = State\Entity::BANKING_ACCOUNT_ID;

        $subquery = DB::table($bankingAccountState)
                ->select($bankingAccountId, DB::raw('max(created_at) as sent_to_bank_date'))
                ->where(State\Entity::STATUS, '=', Status::INITIATED)
                ->whereRaw('( `sub_status` IS NULL or `sub_status` = \'none\' )')
                ->groupBy($bankingAccountId);

        $query->joinSub($subquery, $bankingAccountState, function($join)
        {
            $bankingAccountIdForeignColumn = $this->repo->banking_account_state->dbColumn(State\Entity::BANKING_ACCOUNT_ID);
            $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);

            $join->on($bankingAccountIdColumn, '=', $bankingAccountIdForeignColumn);
        });

        $query->orderBy($sentToBankDate, $sortOrder);
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

    protected function joinQueryActivationDetailCallLog(Base\BuilderEx $query)
    {
        $activationDetailCallLogTable = $this->repo->banking_account_call_log->getTableName();

        if ($query->hasJoin($activationDetailCallLogTable) === true)
        {
            return;
        }

        $bankingAccountIdForeignColumn = $this->repo->banking_account_call_log->dbColumn(Activation\CallLog\Entity::BANKING_ACCOUNT_ID);

        $bankingAccountIdColumn = $this->repo->banking_account->dbColumn(Entity::ID);

        $query->join($activationDetailCallLogTable, $bankingAccountIdColumn, '=', $bankingAccountIdForeignColumn);

        $query->groupBy($bankingAccountIdColumn);
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

    public function addQueryParamFosCity(Base\BuilderEx $query, array $params)
    {
        $merchantCityColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::MERCHANT_CITY);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive exact match for merchant email
        $fosCity = $params[Entity::FOS_CITY];

        if ($fosCity === Constants::NON_FOS)
        {
            $query->whereNotIn($merchantCityColumn, Constants::FOS_CITIES);
        }
        else
        {
            $query->where($merchantCityColumn, '=', $fosCity);
        }
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
        $query->where($merchantCityColumn, '=', $merchantCity);

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

    public function addQueryParamSalesTeam(Base\BuilderEx $query, array $params)
    {
        $salesTeamColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::SALES_TEAM);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive
        $salesTeam = $params[Entity::SALES_TEAM];

        $query->where($salesTeamColumn, '=', $salesTeam);
    }

    public function addQueryParamBusinessPanValidation(Base\BuilderEx $query, array $params)
    {
        $businessPanValidationColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BUSINESS_PAN_VALIDATION);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive
        $businessPanValidation = $params[Entity::BUSINESS_PAN_VALIDATION];

        $query->where($businessPanValidationColumn, '=', $businessPanValidation);
    }

    public function addQueryParamDeclarationStep(Base\BuilderEx $query, array $params)
    {
        $declarationStepColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::DECLARATION_STEP);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive
        $declarationStep = $params[Entity::DECLARATION_STEP];

        $query->where($declarationStepColumn, '=', $declarationStep);
    }

    public function addQueryParamBusinessCategory(Base\BuilderEx $query, array $params)
    {
        $businessCategoryColumn = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BUSINESS_CATEGORY);

        $this->joinQueryActivationDetail($query);

        // selecting only banking_accounts columns so that
        // clashes between field names do not result in corrputed data
        // For example, both merchants and banking_accounts have field 'channel'
        $query->select($this->dbColumn('*'));

        // case insensitive
        $businessCategory = $params[Entity::BUSINESS_CATEGORY];

        $query->where($businessCategoryColumn, '=', $businessCategory);
    }

    public function addQueryParamClarityContext(Base\BuilderEx $query, array $params)
    {
        $merchantAttributeTable = $this->repo->merchant_attribute->getTableName();

        if ($query->hasJoin($merchantAttributeTable) === true)
        {
            return;
        }

        $merchantIdForeignColumn = $this->repo->merchant_attribute->dbColumn(Merchant\Attribute\Entity::MERCHANT_ID);

        $merchantIdColumn = $this->repo->banking_account->dbColumn(Entity::MERCHANT_ID);

        $query->join($merchantAttributeTable, $merchantIdColumn, '=', $merchantIdForeignColumn);

        $query->select($this->dbColumn('*'));

        $merchantAttributeValueColumn = $this->repo->merchant_attribute->dbColumn(Merchant\Attribute\Entity::VALUE);

        // case insensitive
        $clarityContextState = $params[Entity::CLARITY_CONTEXT];

        $query->where($merchantAttributeValueColumn, '=', $clarityContextState);
    }

    /**
     * Filter to search leads by ops follow-up date
     */
    public function addQueryParamFromOpsFollowUpDate(Base\BuilderEx $query, $params)
    {
        $fromOpsFollowUpDate = $params[Entity::FROM_OPS_FOLLOW_UP_DATE];

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."ops_follow_up_date"\')) != \'\' AND ' .
            'JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."ops_follow_up_date"\')) >= \''.$fromOpsFollowUpDate.'\'');
    }

    public function addQueryParamToOpsFollowUpDate(Base\BuilderEx $query, $params)
    {
        $toOpsFollowUpDate = $params[Entity::TO_OPS_FOLLOW_UP_DATE];

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."ops_follow_up_date"\')) != \'\' AND ' .
            'JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."ops_follow_up_date"\')) <= \''.$toOpsFollowUpDate.'\'');
    }

    public function addQueryParamSkipDwt(Base\BuilderEx $query, $params)
    {
        $skipDwtValue = (int)$params[Entity::SKIP_DWT];

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        // skip_dwt = 0 should return all app with null value and 0 value
        if ($skipDwtValue === 1)
        {
            $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."skip_dwt"\')) != \'\' AND ' .
                'JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."skip_dwt"\')) = ?',[$skipDwtValue]);
        } else
        {
            $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."skip_dwt"\')) == \'\' OR ' .
                'JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."skip_dwt"\')) = ?',[$skipDwtValue]);
        }


    }

    /**
     * Filter to fetch applications by docket estimated delivery date
     */
    public function addQueryParamFromDocketEstimatedDeliveryDate(Base\BuilderEx $query, $params)
    {
        $fromDocketEstimatedDeliveryDate = $params[Entity::FROM_DOCKET_ESTIMATED_DELIVERY_DATE];

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."docket_estimated_delivery_date"\')) != \'\' AND ' .
            'JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."docket_estimated_delivery_date"\')) >= \''.$fromDocketEstimatedDeliveryDate.'\'');
    }

    public function addQueryParamToDocketEstimatedDeliveryDate(Base\BuilderEx $query, $params)
    {
        $toDocketEstimatedDeliveryDate = $params[Entity::TO_DOCKET_ESTIMATED_DELIVERY_DATE];

        $this->joinQueryActivationDetail($query);

        $query->select($this->dbColumn('*'));

        $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."docket_estimated_delivery_date"\')) != \'\' AND ' .
            'JSON_UNQUOTE(JSON_EXTRACT(additional_details, \'$."docket_estimated_delivery_date"\')) <= \''.$toDocketEstimatedDeliveryDate.'\'');
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

    public function getCAArchivedCohortList(int $startTime, int $endTime)
    {
        $bankingAccountStateStatusColumn                    = $this->repo->banking_account_state->dbColumn(BankingAccountState\Entity::STATUS);
        $bankingAccountStateCreatedAtColumn                 = $this->repo->banking_account_state->dbColumn(BankingAccountState\Entity::CREATED_AT);
        $bankingAccountStateBankingAccountIdColumn          = $this->repo->banking_account_state->dbColumn(BankingAccountState\Entity::BANKING_ACCOUNT_ID);
        $activationStatus                                   = $this->dbColumn(Entity::STATUS);
        $accountTypeColumn                                  = $this->dbColumn(Entity::ACCOUNT_TYPE);
        $idColumn                                           = $this->dbColumn(Entity::ID);

        $selectAttr                 = [
            $this->dbColumn(Entity::MERCHANT_ID),
        ];

        return $this->newQuery()
            ->select($selectAttr)
            ->join(Table::BANKING_ACCOUNT_STATE, $idColumn, '=', $bankingAccountStateBankingAccountIdColumn)
            ->where($accountTypeColumn, '=', AccountType::CURRENT)
            ->where($activationStatus, '=', Status::ARCHIVED)
            ->where($bankingAccountStateStatusColumn, '=', Status::ARCHIVED)
            ->whereBetween($bankingAccountStateCreatedAtColumn, [$startTime, $endTime])
            ->groupBy(Entity::MERCHANT_ID)
            ->get();
    }

    public function getStatusWithMerchantId(string $merchantId)
    {
        $activationStatus      = $this->dbColumn(Entity::STATUS);
        $merchantIdColumn      = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select([$activationStatus])
                    ->where($merchantIdColumn, '=', $merchantId)
                    ->get()
                    ->pluck(Entity::STATUS);
    }

    public function getBankingAccountWithBalanceViaAccountNumberAndMerchantId($accountNumber, $merchantId)
    {
        return $this->newQuery()
                    ->with(['balance'])
                    ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->firstOrFail();
    }

    public function getBankingAccountViaAccountNumberAndIfsc($accountNumber, $ifsc)
    {
        return $this->newQuery()
            ->where(Entity::ACCOUNT_NUMBER, $accountNumber)
            ->where(Entity::ACCOUNT_IFSC, $ifsc)
            ->first();
    }

    public function fetchBankingAccountByMerchantIdAccountTypeChannelAndStatus(string $merchantId, string $channel, string $accountType, string $status = null)
    {
        $query = $this->newQuery()
                      ->where(Entity::MERCHANT_ID, $merchantId)
                      ->where(Entity::CHANNEL, $channel)
                      ->where(Entity::ACCOUNT_TYPE, $accountType);

        if (empty($status) === false)
        {
            $query->where(Entity::STATUS, $status);
        }

        return $query->first();
    }

    public function fetchBankingAccountsByMerchantIdAccountTypeChannel(string $merchantId, string $channel, string $accountType)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::CHANNEL, $channel)
            ->where(Entity::ACCOUNT_TYPE, $accountType)
            ->get();
    }

    public function getMerchantPocAndBeneficiaryEmail(string $merchantId)
    {
        $activationDetailsTableBankingAccountId = $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::BANKING_ACCOUNT_ID);

        $bankingAccountTableId = $this->dbColumn(Entity::ID);
        $bankingAccountTableMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

        $selectAtr = [
            $this->repo->banking_account_activation_detail->dbColumn(ActivationDetail\Entity::MERCHANT_POC_EMAIL),
            $this->dbColumn(Entity::BENEFICIARY_EMAIL)
        ];

        $query = $this->newQuery()
            ->select($selectAtr)
            ->join(Table::BANKING_ACCOUNT_ACTIVATION_DETAIL, $bankingAccountTableId, '=', $activationDetailsTableBankingAccountId)
            ->where($bankingAccountTableMerchantId, '=', $merchantId)
            ->get();

        return [
            ActivationDetail\Entity::MERCHANT_POC_EMAIL => $query->pluck(ActivationDetail\Entity::MERCHANT_POC_EMAIL)->first(),
            Entity::BENEFICIARY_EMAIL                   => $query->pluck(Entity::BENEFICIARY_EMAIL)->first()
        ];
    }

    public function fetchMerchantsWithCaRblAccount(
        int $limit,
        int $skip,
        string $channel,
        string $accountType,
        array $merchantIds = [],
        array $merchantIdsExcluded = []): array
    {
        $channelColumn = $this->dbColumn(Entity::CHANNEL);
        $accountTypeColumn = $this->dbColumn(Entity::ACCOUNT_TYPE);
        $statusColumn = $this->dbColumn(Entity::STATUS);

        $merchantActivatedColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ACTIVATED);
        $merchantIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $bankingAccountMerchantIdColumn = $this->repo->banking_account->dbColumn(Entity::MERCHANT_ID);

        $query =  $this->newQueryWithConnection($this->getSlaveConnection())
            ->join(Table::MERCHANT, $bankingAccountMerchantIdColumn, '=', $merchantIdColumn)
            ->where($channelColumn, '=', $channel)
            ->where($accountTypeColumn, '=', $accountType)
            ->where($statusColumn, '=', Status::ACTIVATED)
            ->where($merchantActivatedColumn, '=', 0)
            ->where(function ($query)
            {
                $query->whereNotIn(Merchant\Entity::PARENT_ID, Preferences::NO_MERCHANT_INVOICE_PARENT_MIDS)
                    ->orWhereNull(Merchant\Entity::PARENT_ID);
            })
            ->take($limit)
            ->skip($skip);

        if (empty($merchantIds) === false)
        {
            $query = $query->whereIn($merchantIdColumn, $merchantIds);
        }

        if (empty($merchantIdsExcluded) === false)
        {
            $query = $query->whereNotIn($merchantIdColumn, $merchantIdsExcluded);
        }

        return $query->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function getValidBankingAccountIds(string $channel, string $accountType, array $bankingAccountIds): array
    {
        $bankingAccountId = $this->repo->banking_account->dbColumn(Entity::ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->whereIn($bankingAccountId, $bankingAccountIds)
                    ->where(Entity::CHANNEL, $channel)
                    ->where(Entity::ACCOUNT_TYPE, $accountType)
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function saveOrFail($entity, array $options = array())
    {
        $this->repo->transaction(function()
            use ($entity, $options)
        {
            parent::saveOrFail($entity, $options);

            $metro = new Metro();

            $metro->publishToMetro($entity);
        });
    }
}
