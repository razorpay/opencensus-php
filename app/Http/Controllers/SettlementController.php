<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use Response;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity;
use RZP\Constants\Entity as E;
use RZP\Models\Batch;

class SettlementController extends Controller
{

    public function createSettlementEntry()
    {
        $input = Request::all();

        $data = $this->service()->createSettlementEntry($input);

        return ApiResponse::json($data);
    }

    public function postSettlementInitiate($channel = null)
    {
        $input = Request::all();

        $data = $this->service()->initiateSettlements($input, $channel);

        return ApiResponse::json($data);
    }

    public function postSettlementBucketBackfill()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_BUCKET)->fillSettlementBucket($input);

        return ApiResponse::json($data);
    }

    public function deleteCompletedBucketEntries()
    {
        $input = Request::all();

        $data = $this->service(Entity::SETTLEMENT_BUCKET)->deleteCompletedBucketEntries($input);

        return ApiResponse::json($data);
    }

    public function postSettlementRetry()
    {
        $input = Request::all();

        $data = $this->service()->processFailedSettlements($input);

        return ApiResponse::json($data);
    }

    public function getMerchantSettlementAmount()
    {
        $input = Request::all();

        $data = $this->service()->getMerchantSettlementAmount($input);

        return ApiResponse::json($data);
    }

    /**
     * Initiates settlements for merchants with
     * feature DAILY_SETTLEMENT enabled.
     * These merchants have their settlements created
     * every day, irrespective of holidays, but transfer
     * for these settlements get initiated only on
     * non-holidays at a time defined by the merchant.
     */
    public function processDailySettlements()
    {
        $input = Request::all();

        $data = $this->service()->processDailySettlements($input);

        return ApiResponse::json($data);
    }

    public function postSettlementFileGenerate()
    {
        $input = Request::all();

        $data = $this->service()->generateSettlementFile($input);

        return ApiResponse::json($data);
    }

    public function putEditSettlement($id)
    {
        $input = Request::all();

        $data = $this->service()->editSettlement($id, $input);

        return ApiResponse::json($data);
    }

    public function getSettlement($id)
    {
        $data = $this->service()->fetch($id);

        return ApiResponse::json($data);
    }

    public function getOrgSettlement($id)
    {
        $merchantId = $this->ba->getMerchantId();

        $data = $this->service()->fetchOrgSettlement($id, $merchantId);

        return ApiResponse::json($data);
    }


    public function getSettlements()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcileThroughFile(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->reconcileSettlementsThroughFile($input, $channel);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcileThroughApi(string $channel)
    {
        $data = $this->service()->settlementReconcileThroughApi($this->input, $channel);

        return ApiResponse::json($data);
    }

    public function postH2HSettlementReconcile(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->reconcileH2HSettlements($input, $channel);

        return ApiResponse::json($data);
    }

    public function postSettlementReconcileFileGenerate(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->generateSettlementReconciliationFile($input, $channel);

        return ApiResponse::json($data);
    }

    public function postReconcileInTestMode()
    {
        $input = Request::all();

        $data = $this->service()->reconcileSettlementsInTestMode($input);

        return ApiResponse::json($data);
    }

    public function deleteSettlementFile($setlFileType)
    {
        $data = $this->service()->deleteSetlFile($setlFileType);

        return ApiResponse::json($data);
    }

    public function getSettlementTransactions($id)
    {
        $data = $this->service()->fetchSettlementTransactions($id);

        return ApiResponse::json($data);
    }


    public function getSettlementDetails($id)
    {
        $data = $this->service(E::SETTLEMENT_DETAILS)->getSettlementDetails($id);

        return ApiResponse::json($data);
    }

    public function postSettlementDetailsForOldTxns()
    {
        $input = Request::all();

        $data = $this->service(E::SETTLEMENT_DETAILS)->postSettlementDetailsForOldTxns($input);

        return ApiResponse::json($data);
    }

    public function getSettlementCombinedReport()
    {
        $input = Request::all();

        $data = $this->service()->getSettlementCombinedReport($input);

        return ApiResponse::json($data);
    }

    public function getSettlementCombinedReconReport()
    {
        $input = Request::all();

        $data = $this->service()->getSettlementCombinedReconReport($input);

        return ApiResponse::json($data);
    }

    public function updateChannelForMultipleSettlements()
    {
        $input = Request::all();

        $data = $this->service()->updateChannelForMultipleSettlements($input);

        return ApiResponse::json($data);
    }

    public function postInitiateTransfer()
    {
        $input = Request::all();

        $data = $this->service()->postInitiateTransfer($input);

        return ApiResponse::json($data);
    }

    public function addBeneficiary(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->addBeneficiary($channel, $input);

        return ApiResponse::json($data);
    }

    public function getAccountBalance(string $channel)
    {
        $data = $this->service()->getAccountBalance($channel);

        return ApiResponse::json($data);
    }

    public function postSettlementVerifyThroughApi(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->verifySettlementsThroughApi($input, $channel);

        return ApiResponse::json($data);
    }

    public function postH2HSettlementNotifyErrors(string $channel)
    {
        $input = Request::all();

        $data = $this->service()->notifyH2HErrors($input, $channel);

        return ApiResponse::json($data);
    }

    /**
     * Initiates settlements for merchants with
     * feature ADHOC_SETTLEMENT enabled.
     * These merchants have their settlements created
     * every day, irrespective of holidays, but transfer
     * for these settlements get initiated only on
     * non-holidays at a time defined by the merchant.
     */
    public function processAdhocSettlements()
    {
        $input = Request::all();

        $data = $this->service()->processAdhocSettlements($input);

        return ApiResponse::json($data);
    }

    /**
     * gets the settlement process details
     * contains intermediate cached data of settlement
     */
    public function getSettlementProcess()
    {
        $data = $this->service()->getProcessDetails();

        return ApiResponse::json($data);
    }

    /**
     * delete all process data
     * this is required to clear the config if terminated unexpectedly
     */
    public function resetSettlementProcess()
    {
        $this->service()->resetProcessDetails();

        $data = $this->service()->getProcessDetails();

        return ApiResponse::json($data);
    }

    public function getHolidayListForYear()
    {
        $input = Request::all();

        $data = $this->service()->getHolidayListForYear($input);

        return ApiResponse::json($data);
    }

    public function getSettlementTransactionsWithSettlementId($id)
    {
        $input = Request::all();

        $data = $this->service()->getSettlementTransactionsWithSettlementId($id, $input);

        return ApiResponse::json($data);
    }

    public function getSettlementTransactionsSourceDetails($id)
    {
        $input = Request::all();

        $data = $this->service()->getSettlementTransactionsSourceDetails($id, $input);

        return ApiResponse::json($data);
    }

    public function serviceFetch()
    {
        $input = Request::all();

        $data = $this->service()->serviceFetch($input);

        return ApiResponse::json($data);
    }

    public function serviceFetchMultiple()
    {
        $input = Request::all();

        $data = $this->service()->serviceFetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function merchantConfigGet()
    {
        $input = Request::all();

        $data = $this->service()->merchantConfigGet($input);

        return ApiResponse::json($data);
    }

    public function merchantConfigCreate()
    {
        $input = Request::all();

        $data = $this->service()->merchantConfigCreate($input);

        return ApiResponse::json($data);
    }

    public function orgConfigGet()
    {
        $input = Request::all();

        $data = $this->service()->orgConfigGet($input);

        return ApiResponse::json($data);
    }

    public function orgConfigCreateOrUpdate()
    {
        $input = Request::all();

        $data = $this->service()->orgConfigCreateOrUpdate($input);

        return ApiResponse::json($data);
    }

    public function merchantConfigUpdate()
    {
        $input = Request::all();

        $data = $this->service()->merchantConfigUpdate($input);

        return ApiResponse::json($data);
    }

    public function merchantConfigBulkUpdate()
    {
        $input = Request::all();

        $data = $this->service()->merchantConfigBulkUpdate($input);

        return ApiResponse::json($data);
    }

    public function fetchSettlementDetails()
    {
        $input = Request::all();

        $data = $this->service()->settlementTimeline($input);

        return ApiResponse::json($data);
    }

    public function merchantConfigGetScheduleableEntities()
    {
        $data = $this->service()->merchantConfigGetScheduleableEntities();

        return ApiResponse::json($data);
    }

    public function bankAccountCreate()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountCreate($input);

        return ApiResponse::json($data);
    }

    public function bankAccountUpdate()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountUpdate($input);

        return ApiResponse::json($data);
    }

    public function bankAccountGet()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountGet($input);

        return ApiResponse::json($data);
    }

    public function bankAccountDelete()
    {
        $input = Request::all();

        $data = $this->service()->bankAccountDelete($input);

        return ApiResponse::json($data);
    }

    public function scheduleCreate()
    {
        $input = Request::all();

        $data = $this->service()->scheduleCreate($input);

        return ApiResponse::json($data);
    }

    public function scheduleGet()
    {
        $input = Request::all();

        $data = $this->service()->scheduleGet($input);

        return ApiResponse::json($data);
    }

    public function scheduleRename()
    {
        $input = Request::all();

        $data = $this->service()->scheduleRename($input);

        return ApiResponse::json($data);
    }

    public function triggerReport()
    {
        $input = Request::all();

        $data = $this->service()->triggerReport($input);

        return ApiResponse::json($data);
    }

    public function transferStatusUpdate()
    {
        $input = Request::all();

        $data = $this->service()->transferStatusUpdate($input);

        return ApiResponse::json($data);
    }

    public function migrateToPayout()
    {
        $input = Request::all();

        $data = $this->service()->migrateToPayout($input);

        return ApiResponse::json($data);
    }

    public function scheduleGetIds()
    {
        $input = Request::all();

        $data = $this->service()->scheduleGetIds($input);

        return ApiResponse::json($data);
    }

    public function sendGifuFile($orgId)
    {
        $input = Request::all();

        $data = $this->service()->sendGifuFile($input, $orgId);

        return ApiResponse::json($data);
    }

    public function getNiumFile()
    {
        $input = Request::all();
        $this->increaseAllowedSystemLimits();
        $data = $this->service()->getNiumFile($input);

        return ApiResponse::json($data);
    }

    public function onholdClearForImportFlow()
    {
        $input = Request::all();
        $this->increaseAllowedSystemLimits();
        $data = $this->service()->onholdClearForImportFlow($input);

        return ApiResponse::json($data);
    }

    public function sendIciciOpgspImportSettlementFile()
    {
        $input = Request::all();
        $this->increaseAllowedSystemLimits();
        $data = $this->service()->sendIciciOpgspImportSettlementFile($input);

        return ApiResponse::json($data);
    }

    public function sendIciciOpgspImportInvoices()
    {
        $input = Request::all();
        $this->increaseAllowedSystemLimits();
        $data = $this->service()->sendIciciOpgspImportInvoices($input);

        return ApiResponse::json($data);
    }

    public function replaySettlementTransactions()
    {
        $input = Request::all();

        $data = $this->service()->replayTransactions($input);

        return ApiResponse::json($data);
    }

    public function replaySettlementTransactionsAdmin()
    {
        $input = Request::all();

        $data = $this->service()->replayTransactions($input);

        return ApiResponse::json($data);
    }

    public function executionReminder()
    {
        $input = Request::all();

        $data = $this->service()->executionReminder($input);

        return ApiResponse::json($data);
    }

    public function getIrctcSettlementFile(string $date)
    {
        $input = Request::all();

        $fileContent = $this->service()->getIrctcSettlementFile($date, $input);

        return ApiResponse::json($fileContent);
    }

    public function postSettlementCreateStatusUpdate()
    {
        $input = Request::all();

        $data = $this->service()->postSettlementCreateStatusUpdate($input);

        return ApiResponse::json($data);
    }

    public function migrateConfigurations()
    {
        $input = Request::all();

        $data = $this->service()->cronRunMigrations($input);

        return ApiResponse::json($data);
    }

    public function migrateBlockedTransactions()
    {
        $input = Request::all();

        $data = $this->service()->migrateBlockedTransactions($input);

        return ApiResponse::json($data);
    }

    public function migrateConfigurationsAdmin()
    {
        $input = Request::all();

        $data = $this->service()->migrateConfigurations($input);

        return ApiResponse::json($data);
    }

    public function executionRegister()
    {
        $input = Request::all();

        $data = $this->service()->executionRegister($input);

        return ApiResponse::json($data);
    }

    public function setDCSObject()
    {
        $input = Request::all();

        $data = $this->service()->setDCSObject($input);

        return ApiResponse::json($data);
    }

    public function executionTriggerMultiple()
    {
        $input = Request::all();

        $data = $this->service()->executionTriggerMultiple($input);

        return ApiResponse::json($data);
    }

    public function executionResume()
    {
        $input = Request::all();

        $data = $this->service()->executionResume($input);

        return ApiResponse::json($data);
    }

    /**
     * RSR-2204 - bulk register reminder service
     * for created execution in CREATED
     * @return mixed
     */
    public function bulkRegisterReminder()
    {
        $input = Request::all();

        $data = $this->service()->bulkRegisterReminder($input);

        return ApiResponse::json($data);
    }

    public function bulkRegisterEntitySchedulerReminder()
    {
        $input = Request::all();

        $data = $this->service()->bulkRegisterEntitySchedulerReminder($input);

        return ApiResponse::json($data);
    }

    public function entitySchedulerTriggerMultiple()
    {
        $input = Request::all();

        $data = $this->service()->entitySchedulerTriggerMultiple($input);

        return ApiResponse::json($data);
    }

    public function transactionHold()
    {
        $input = Request::all();

        $data = $this->service()->transactionHold($input);

        return ApiResponse::json($data);
    }

    public function transactionRelease()
    {
        $input = Request::all();

        $data = $this->service()->transactionRelease($input);

        return ApiResponse::json($data);
    }

    public function channelStatusUpdate()
    {
        $input = Request::all();

        $data = $this->service()->channelStatusUpdate($input);

        return ApiResponse::json($data);
    }

    public function settlementRetry()
    {
        $input = Request::all();

        $data = $this->service()->settlementRetry($input);

        return ApiResponse::json($data);
    }

    public function getChannelState()
    {
        $data = $this->service()->getChannelState();

        return ApiResponse::json($data);
    }

    public function settlementTransactionsVerify()
    {
        $input = Request::all();

        $data = $this->service()->settlementTransactionsVerify($input);

        return ApiResponse::json($data);
    }

    public function replaySettlementsStatusUpdate()
    {
        $input = Request::all();

        $data = $this->service()->replaySettlementsStatusUpdate($input);

        return ApiResponse::json($data);
    }

    public function getSettlementSourceDetails()
    {
        $input = Request::all();

        $data = $this->service()->getSettlementSourceDetails($input);

        return ApiResponse::json($data);
    }

    public function getSettlementSmsNotificationStatus()
    {
        $data = $this->service()->getSettlementSmsNotificationStatus();

        return ApiResponse::json($data);
    }

    public function toggleSettlementSmsNotification()
    {
        $input = Request::all();

        $data = $this->service()->toggleSettlementSmsNotification($input);

        return ApiResponse::json($data);
    }

    public function settlementsLedgerInconsistencyDebug()
    {
        $input = Request::all();

        $data = $this->service()->settlementsLedgerInconsistencyDebug($input);

        return ApiResponse::json($data);
    }

    public function getSettlementServiceEntityFile()
    {
        $input = Request::all();

        $data = $this->service()->getSettlementServiceEntityFile($input);

        return ApiResponse::json($data);
    }

    public function updateBeneName()
    {
        $input = Request::all();

        $data = $this->service()->updateBeneName($input);

        return ApiResponse::json($data);
    }

    public function optimizerExternalSettlementsExecute()
    {
        $input = Request::all();

        $data = $this->service()->optimizerExternalSettlementsExecute($input);

        return ApiResponse::json($data);
    }

    public function optimizerExternalSettlementsManualExecute()
    {
        $input = Request::all();

        $data = $this->service()->optimizerExternalSettlementsManualExecute($input);

        return ApiResponse::json($data);
    }

    public function settlementsInitiate()
    {
        $input = Request::all();

        $data = $this->service()->settlementsInitiate($input);

        return ApiResponse::json($data);
    }

    // RSR-1970 merchant config from merchant dashboard
    public function merchantDashboardConfigGet()
    {
        $input = Request::all();

        $data = $this->service()->merchantDashboardConfigGet($input);

        return ApiResponse::json($data);
    }

    public function settlementsAmountCheck()
    {
        $input = Request::all();

        $data = $this->service()->settlementsAmountCheck($input);

        return ApiResponse::json($data);
    }

    public function checkForEntityAlerts()
    {
        $input = Request::all();

        $data = $this->service()->checkForEntityAlerts($input);

        return ApiResponse::json($data);
    }

    public function initiateInterNodalTransfer()
    {
        $input = Request::all();

        $data = $this->service()->initiateInterNodalTransfer($input);

        return ApiResponse::json($data);
    }

    public function processPosSettlementFile()
    {
        $input = Request::all();

        $response = $this->service()->processPosFile($input, Batch\Type::EZETAP_SETTLEMENT);

        return ApiResponse::json($response);
    }

    public function readCustomSettlementsFile()
    {
        $input = Request::all();

        $response = $this->service()->readCustomSettlementsFile($input);

        return ApiResponse::json($response);
    }

    public function createPosSettlement()
    {
        $input = Request::all();

        $response = $this->service()->createPosSettlement($input);

        return ApiResponse::json($response);
    }

    public function insertExternalTransactionRecord()
    {
        $input = Request::all();

        $data = $this->service()->insertExternalTransactionRecord($input);

        return ApiResponse::json($data);
    }

    public function updateTransactionCountOfExecution()
    {
        $input = Request::all();

        $data = $this->service()->updateTransactionCountOfExecution($input);

        return ApiResponse::json($data);
    }

    public function updateStatusofOptimiserExecution()
    {
        $input = Request::all();

        $data = $this->service()->updateStatusofOptimiserExecution($input);

        return ApiResponse::json($data);
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
    }

}
