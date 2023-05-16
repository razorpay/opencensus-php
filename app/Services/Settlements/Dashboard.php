<?php

namespace RZP\Services\Settlements;

use RZP\Trace\TraceCode;
use RZP\Models\BankAccount\Type;
use RZP\Exception\RuntimeException;

class Dashboard extends Base
{
    //********************* All endpoints for dashboard are configured here ***************************//

    const FETCH_URI                    = '/twirp/rzp.settlements.dashboard.v1.DashboardService/Fetch';
    const FETCH_MULTIPLE_URI           = '/twirp/rzp.settlements.dashboard.v1.DashboardService/FetchMultiple';
    const FETCH_ENTITY_FILE            = '/twirp/rzp.settlements.dashboard.v1.DashboardService/DownloadEntities';

    const SCHEDULE_CREATE_URI          = '/twirp/rzp.settlements.schedule.v1.ScheduleService/Create';
    const SCHEDULE_GET_URI             = '/twirp/rzp.settlements.schedule.v1.ScheduleService/Get';
    const SCHEDULE_RENAME_URI          = '/twirp/rzp.settlements.schedule.v1.ScheduleService/Rename';
    const SCHEDULE_GET_IDS_URI         = '/twirp/rzp.settlements.schedule.v1.ScheduleService/GetAllIds';

    const BANK_ACCOUNT_GET             = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Get';
    const BANK_ACCOUNT_UPDATE          = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Update';
    const BANK_ACCOUNT_DELETE          = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Delete';
    const BENE_NAME_UPDATE             = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/UpdateBeneficiaryName';

    const EXECUTION_REGISTER           = '/twirp/rzp.settlements.execution.v1.ExecutionService/Register';
    const SET_DCS_OBJECT               = '/twirp/rzp.settlements.dcs.v1.DCS/Set';
    const EXECUTION_TRIGGER_MULTIPLE   = '/twirp/rzp.settlements.execution.v1.ExecutionService/TriggerMultiple';
    const EXECUTION_RESUME             = '/twirp/rzp.settlements.execution.v1.ExecutionService/Resume';
    const BULK_REGISTRATION_REMINDER   = '/twirp/rzp.settlements.execution.v1.ExecutionService/BulkRegisterReminders';
    const BULK_REGISTRATION_ENTITY_SCHEDULER_REMINDER = '/twirp/rzp.settlements.entity_scheduler.v1.EntityScheduler/BulkRegisterEntitySchedulerReminders';
    const ENTITY_SCHEDULER_TRIGGER_MULTIPLE = '/twirp/rzp.settlements.entity_scheduler.v1.EntityScheduler/TriggerMultiple';

    const CHANNEL_STATUS_UPDATE        = '/twirp/rzp.settlements.transfer.v1.TransferService/SetChannelState';
    const CHANNEL_STATUS_GET           = '/twirp/rzp.settlements.transfer.v1.TransferService/GetChannelState';

    const SETTLEMENT_RETRY             = '/twirp/rzp.settlements.settlement.v1.SettlementService/Retry';
    const SETTLEMENT_INITIATE          = '/twirp/rzp.settlements.settlement.v1.SettlementService/ManualSettlementInitiate';

    const REPORT_TRIGGER               = '/twirp/rzp.settlements.report.v1.ReportService/TriggerReport';
    const TRANSFER_STATUS_UPDATE       = '/twirp/rzp.settlements.transfer.v1.TransferService/UpdateStatus';

    const MERCHANT_CONFIG_GET          = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Get';
    const ORG_CONFIG_CREATE_OR_UPDATE  = '/twirp/rzp.settlements.org_settlement_config.v1.OrgConfigService/CreateOrUpdate';
    const MERCHANT_CONFIG_GET_SCHEDULABLE_ENTITIES = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/SchedulableEntities';

    const REPLAY_SETTLEMENTS_STATUS_UPDATE = '/twirp/rzp.settlements.settlement.v1.SettlementService/ReplaySettlementUpdate';

    const MIGRATE_TO_PAYOUT                = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/MigrateToPayout';

    const ENTITIES = 'entities';
    const CONFIG   = 'config';
    const TYPES = 'types';
    const INITIATE_TYPES ='initiate_types';
    const SETTLE_TO_ORG ='settle_to_org';
    const AGGREGATE = 'aggregate';
    const ENABLE = 'enable';
    const SCHEDULE_ID ='schedule_id';
    const TRANSACTION_LEVEL = 'transaction_level';
    const PREFERENCES = 'preferences';
    const ZERO_DS_IGNORE = 'zero_ds_ignore';
    const DEFAULT = 'default';
    const DELAYED = 'delayed';
    const FEATURES = 'features';
    const BLOCK = 'block';
    const HOLD = 'hold';
    const STATUS = 'status';
    const AGGREGATE_SETTLEMENT_PARENT = 'aggregate_settlement_parent';
    const DAILY_SETTLEMENT ='daily_settlement';

    public function __construct($app)
    {
        parent::__construct($app);

        $this->setHeader();
    }

    /**
     * Dashboard Service fetch
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function fetch(array $input) : array
    {
        return $this->makeRequest(self::FETCH_URI, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Dashboard Service fetchMultiple
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function fetchMultiple(array $input) : array
    {
        return $this->makeRequest(self::FETCH_MULTIPLE_URI, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Schedule Service Create
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function scheduleCreate(array $input) : array
    {
        return $this->makeRequest(self::SCHEDULE_CREATE_URI, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Schedule Service Get
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function scheduleGet(array $input) : array
    {
        return $this->makeRequest(self::SCHEDULE_GET_URI, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Schedule Service Rename
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function scheduleRename(array $input) : array
    {
        return $this->makeRequest(self::SCHEDULE_RENAME_URI, $input, self::SERVICE_DASHBOARD);
    }


    /**
     * trigger report
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function triggerReport(array $input) : array
    {
        return $this->makeRequest(self::REPORT_TRIGGER, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * transfer status update
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function transferStatusUpdate(array $input) : array
    {
        return $this->makeRequest(self::TRANSFER_STATUS_UPDATE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Schedule Service GetIds
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function scheduleGetIds(array $input) : array
    {
        return $this->makeRequest(self::SCHEDULE_GET_IDS_URI, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Merchant Config Service Get
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigGet(array $input) : array
    {
        return $this->makeRequest(self::MERCHANT_CONFIG_GET, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Org Config Service Get
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function orgConfigGet(array $input) : array
    {
        return $this->makeRequest(self::ORG_CONFIG_GET, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Merchant Config Service Create
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigCreate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::MERCHANT_CONFIG_CREATE, $input, self::SERVICE_DASHBOARD, $mode);
    }

    /**
     * Org Config Service Create
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function orgConfigCreateOrUpdate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::ORG_CONFIG_CREATE_OR_UPDATE, $input, self::SERVICE_DASHBOARD, $mode);
    }

    /**
     * Merchant Config Service Update
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigUpdate(array $input, $mode = null) : array
    {
        $this->trace->debug(TraceCode::SETTLEMENT_DEBUG_LOG, [
            'INPUT' => $input,
            'msg'=> 'data req',
        ]);
        return $this->makeRequest(self::MERCHANT_CONFIG_UPDATE, $input, self::SERVICE_DASHBOARD, $mode);
    }
    /**
     * Merchant Config Service Bulk Update
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */

    public function merchantConfigBulkUpdate(array $input, $mode = null) : array
    {
        if (isset($input[self::ENTITIES][self::TYPES][self::AGGREGATE][self::ENABLE]) === true)
        {
            $input[self::ENTITIES][self::TYPES][self::AGGREGATE][self::ENABLE]  = ($input[self::ENTITIES][self::TYPES][self::AGGREGATE][self::ENABLE] == true);
        }
        if (isset($input[self::ENTITIES][self::TYPES][self::DEFAULT][self::ENABLE]) === true)
        {
            $input[self::ENTITIES][self::TYPES][self::DEFAULT][self::ENABLE]  = ($input[self::ENTITIES][self::TYPES][self::DEFAULT][self::ENABLE] == true);
        }
        if (isset($input[self::ENTITIES][self::TYPES][self::TRANSACTION_LEVEL][self::ENABLE]) === true)
        {
            $input[self::ENTITIES][self::TYPES][self::TRANSACTION_LEVEL][self::ENABLE]  = ($input[self::ENTITIES][self::TYPES][self::TRANSACTION_LEVEL][self::ENABLE] == true);
        }
        if (isset($input[self::ENTITIES][self::INITIATE_TYPES][self::DEFAULT][self::ENABLE]) === true)
        {
            $input[self::ENTITIES][self::INITIATE_TYPES][self::DEFAULT][self::ENABLE]  = ($input[self::ENTITIES][self::INITIATE_TYPES][self::DEFAULT][self::ENABLE] == true);
        }
        if (isset($input[self::ENTITIES][self::INITIATE_TYPES][self::DELAYED][self::ENABLE]) === true)
        {
            $input[self::ENTITIES][self::INITIATE_TYPES][self::DELAYED][self::ENABLE]  = ($input[self::ENTITIES][self::INITIATE_TYPES][self::DELAYED][self::ENABLE] == true);
        }
        if (isset($input[self::ENTITIES][self::PREFERENCES][self::ZERO_DS_IGNORE]) === true)
        {
            $input[self::ENTITIES][self::PREFERENCES][self::ZERO_DS_IGNORE]  = ($input[self::ENTITIES][self::PREFERENCES][self::ZERO_DS_IGNORE] == true);
        }
        if (isset($input[self::ENTITIES][self::FEATURES][self::BLOCK][self::STATUS]) === true)
        {
            $input[self::ENTITIES][self::FEATURES][self::BLOCK][self::STATUS]  = ($input[self::ENTITIES][self::FEATURES][self::BLOCK][self::STATUS] == true);
        }
        if (isset($input[self::ENTITIES][self::FEATURES][self::HOLD][self::STATUS]) === true)
        {
            $input[self::ENTITIES][self::FEATURES][self::HOLD][self::STATUS]  = ($input[self::ENTITIES][self::FEATURES][self::HOLD][self::STATUS] == true);
        }
        if (isset($input[self::ENTITIES][self::PREFERENCES][self::AGGREGATE_SETTLEMENT_PARENT]) === true)
        {
            $input[self::ENTITIES][self::PREFERENCES][self::AGGREGATE_SETTLEMENT_PARENT]  = ($input[self::ENTITIES][self::PREFERENCES][self::AGGREGATE_SETTLEMENT_PARENT] == true);
        }
        if (isset($input[self::ENTITIES][self::PREFERENCES][self::DAILY_SETTLEMENT]) === true)
        {
            $input[self::ENTITIES][self::PREFERENCES][self::DAILY_SETTLEMENT]  = ($input[self::ENTITIES][self::PREFERENCES][self::DAILY_SETTLEMENT] == true);
        }

        return $this->makeRequest(self::MERCHANT_CONFIG_BULK_UPDATE, $input, self::SERVICE_DASHBOARD, $mode);
    }

    /**
     * Merchant Config Service GetAll Scheduliable Entities
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantConfigGetScheduleableEntities($mode = null): array
    {
        return $this->makeRequest(self::MERCHANT_CONFIG_GET_SCHEDULABLE_ENTITIES, [], self::SERVICE_DASHBOARD, $mode);
    }

    /**
     * Bank Account Service Create
     * @param array  $input
     * @param null $mode
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountCreate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::BANK_ACCOUNT_CREATE, $input, self::SERVICE_DASHBOARD, $mode);
    }

    public function orgBankAccountCreate(array $input, $mode = null) : array
    {
        return $this->makeRequest(self::ORG_BANK_ACCOUNT_CREATE, $input, self::SERVICE_DASHBOARD, $mode);
    }

    /**
     * Bank Account Service Update
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountUpdate(array $input) : array
    {
        return $this->makeRequest(self::BANK_ACCOUNT_UPDATE, $input, self::SERVICE_DASHBOARD);
    }

    public function orgBankAccountUpdate( $input) : array
    {

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_ORG_BANK_ACCOUNT_UPDATE,
            [
                "info" => "updating org bank account in settlements",
                "org_id" => $input["entity_id"]
            ]);

        $req = $this->getBankAccountCreateRequestForSettlementService($input,'payout', true);

        return $this->makeRequest(self::ORG_BANK_ACCOUNT_UPDATE, $req, self::SERVICE_DASHBOARD);
    }

    /**
     * Bank Account Service Get
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountGet(array $input) : array
    {
        return $this->makeRequest(self::BANK_ACCOUNT_GET, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Bank Account Service Delete
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bankAccountDelete(array $input) : array
    {
        return $this->makeRequest(self::BANK_ACCOUNT_DELETE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to create the bank account when the merchant is onboarded
     * @param $input
     * @param $mode
     * @return array|null
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function createBankAccount($input, $mode, $isOrgAccount = false, $merchant = null)
    {
        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_BANK_ACCOUNT_REQUEST,
            [
                'info' => "creating org bank account in settlements",
                "org_id" => $input["entity_id"]
            ]);

        if (($input->getType() !== Type::MERCHANT) and ($input->getType() !== Type::ORG))
        {
            return null;
        }

        $req = $this->getBankAccountCreateRequestForSettlementService($input,'payout', $isOrgAccount, $merchant);

        (new Validator)->validateInput('create_bank_account', $req);

        if ($input->getType() === Type::ORG) {
            return $this->orgBankAccountCreate($req, $mode);
        }
        return $this->bankAccountCreate($req, $mode);
    }

    /**
     * migrateToPayout migrate the fts merchant to payout for settlement
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function migrateToPayout(array $input) : array
    {
        return $this->makeRequest(self::MIGRATE_TO_PAYOUT, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to register the execution
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function executionRegister(array $input) : array
    {
        if (isset($input['options']['force']))
        {
            $input['options']['force'] = ($input['options']['force'] == '1');
        }

        // adding manual conversion of options from the data
        $input['options'] = (object) $input['options'];

        return $this->makeRequest(self::EXECUTION_REGISTER, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to register the execution
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function setDCSObject(array $input) : array
    {
        return $this->makeRequest(self::SET_DCS_OBJECT, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * used to trigger multiple executions at once
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function executionTriggerMultiple(array $input) : array
    {
        if (isset($input['force']) === true)
        {
            $input['force'] = ($input['force'] == '1');
        }

        return $this->makeRequest(self::EXECUTION_TRIGGER_MULTIPLE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to resume the execution
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function executionResume(array $input) : array
    {
        return $this->makeRequest(self::EXECUTION_RESUME, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * RSR-2204 - bulk register reminder service
     * for created execution in CREATED
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function bulkRegisterReminder(array $input) : array
    {
        return $this->makeRequest(self::BULK_REGISTRATION_REMINDER, $input, self::SERVICE_DASHBOARD);
    }

    public function bulkRegisterEntitySchedulerReminder(array $input) :array
    {
        return $this->makeRequest(self::BULK_REGISTRATION_ENTITY_SCHEDULER_REMINDER, $input, self::SERVICE_DASHBOARD);
    }

    public function entitySchedulerTriggerMultiple(array $input) : array
    {
        $input['entity_type']='settlement';
        return $this->makeRequest(self::ENTITY_SCHEDULER_TRIGGER_MULTIPLE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to hold the transaction
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function transactionHold(array $input) : array
    {
        return $this->makeRequest(self::TRANSACTION_HOLD, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to release the transaction
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function transactionRelease(array $input) : array
    {
        return $this->makeRequest(self::TRANSACTION_RELEASE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to Update the channel status
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function channelStatusUpdate(array $input) : array
    {
        return $this->makeRequest(self::CHANNEL_STATUS_UPDATE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Used to retry the settlement
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function settlementRetry(array $input) : array
    {
        return $this->makeRequest(self::SETTLEMENT_RETRY, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * This is used to get the channel state in the settlements service
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function getChannelState() : array
    {
        return $this->makeRequest(self::CHANNEL_STATUS_GET, [], self::SERVICE_DASHBOARD);
    }

    /**
     * Used to replay the settlement status update
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function replaySettlementsStatusUpdate(array $input) : array
    {
        return $this->makeRequest(self::REPLAY_SETTLEMENTS_STATUS_UPDATE, $input, self::SERVICE_DASHBOARD);
    }

    public function getSettlementServiceEntityFile(array $input) : array
    {
        $input['include_deleted'] = (isset($input['include_deleted']) === true) ? ($input['include_deleted'] == '1') : false;

        return $this->makeRequest(self::FETCH_ENTITY_FILE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * Bank Account Service Update
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function updateBeneName(array $input) : array
    {
        return $this->makeRequest(self::BENE_NAME_UPDATE, $input, self::SERVICE_DASHBOARD);
    }

    /**
     * settlement service manual settlement initiate
     * @param array $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function settlementsInitiate(array $input) : array
    {
        return $this->makeRequest(self::SETTLEMENT_INITIATE, $input, self::SERVICE_DASHBOARD);
    }
}
