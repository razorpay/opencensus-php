<?php

namespace RZP\Models\Payout\DataMigration;

use App;
use Carbon\Carbon;
use Illuminate\Foundation\Application;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\Entity;
use RZP\Base\RepositoryManager;
use RZP\Models\Payout\DataMigration\Payout as PayoutMigration;
use RZP\Models\Payout\DataMigration\Reversal as ReversalMigration;
use RZP\Models\Payout\DataMigration\PayoutDetails as PayoutDetailsMigration;
use RZP\Models\Payout\DataMigration\PayoutLogs as PayoutLogsMigration;
use RZP\Models\Payout\DataMigration\PayoutSource as PayoutSourceMigration;
use RZP\Models\Workflow\Service\EntityMap\Entity as WorkflowEntityMapEntity;
use RZP\Models\Payout\DataMigration\WorkflowStateMap as WorkflowStateMapMigration;
use RZP\Models\Payout\DataMigration\WorkflowEntityMap as WorkflowEntityMapMigration;
use RZP\Models\Payout\DataMigration\PayoutStatusDetails as PayoutStatusDetailsMigration;

class Processor
{
    const QUERY_PARAMS = 'query_params';
    const FROM         = 'from';
    const TO           = 'to';

    // Payout Service side entities.
    const PAYOUTS               = 'payouts';
    const REVERSALS             = 'reversals';
    const PAYOUT_LOGS           = 'payout_logs';
    const PAYOUT_DETAILS        = 'payout_details';
    const PAYOUT_SOURCES        = 'payout_sources';
    const WORKFLOW_ENTITY_MAP   = 'workflow_entity_map';
    const WORKFLOW_STATE_MAP   = 'workflow_state_map';
    const PAYOUT_STATUS_DETAILS = 'payout_status_details';

    const PS_TABLE_PREFIX     = 'ps_';
    const MIGRATED_DATA_COUNT = 'migrated_data_count';

    const ID              = Entity::ID;
    const CREATED_AT      = Entity::CREATED_AT;
    const END_TIMESTAMP   = 'end_timestamp';
    const SECONDS_PER_DAY = Carbon::SECONDS_PER_MINUTE * Carbon::MINUTES_PER_HOUR * Carbon::HOURS_PER_DAY;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     *
     * @var Trace
     */
    protected $trace;

    protected $env;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->env = $this->app['env'];

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];
    }

    protected function getPSTableName(string $entity)
    {
        $tableName = $entity;

        if (in_array($this->env, ['testing', 'testing_docker'], true) === true)
        {
            $tableName = self::PS_TABLE_PREFIX . $entity;
        }

        return $tableName;
    }

    public function processDataMigration(array $input)
    {
        $sourceData = $this->repo->payout->getSourceTableData($input);

        $payoutCount = count($sourceData);

        $this->trace->info(
            TraceCode::PAYOUTS_DATA_MIGRATION_PAYOUTS_COUNT,
            ['count' => $payoutCount]
        );

        if ($payoutCount === 0)
        {
            return [];
        }

        // Post dedupe check we keep track of the last payout's id and created at.
        // These will be stored in redis next run will start from after this payout.
        // We will remove the duplicate payouts from $sourceData also.
        list ($lastId, $lastCreatedAt) = $this->dedupeCheckAtDestination($sourceData);

        // Data to be stored on PS will be generated for all payouts.
        $payoutServiceData = $this->getDataForPayoutService($sourceData);

        // For logging purpose.
        $migrationDataCounts = [];

        foreach ($payoutServiceData as $table => $data)
        {
            $migrationDataCounts[$table] = count($data);
        }

        $this->trace->info(
            TraceCode::PAYOUTS_DATA_MIGRATION_DATA_COUNTS,
            ['data' => $migrationDataCounts]
        );

        // Opens a DB transaction on PS DB.
        $this->repo->payout->dbTransactionOnPS(
            function() use ($payoutServiceData) {
                foreach ($payoutServiceData as $table => $data)
                {
                    $this->repo->payout->insertIntoPayoutServiceDB($table, $data);
                }
            }
        );

        return [
            self::MIGRATED_DATA_COUNT => $migrationDataCounts,
            Entity::ID                => $lastId,
            Entity::CREATED_AT        => $lastCreatedAt,
        ];
    }

    // Removes all payouts that are already present in PS.
    protected function dedupeCheckAtDestination(&$sourceData)
    {
        $ids           = [];
        $idKeyMap      = [];
        $lastId        = '';
        $lastCreatedAt = 0;

        foreach ($sourceData as $key => $record)
        {
            array_push($ids, $record[Entity::ID]);

            $idKeyMap[$record[Entity::ID]] = $key;

            $lastId        = $record[Entity::ID];
            $lastCreatedAt = $record[Entity::CREATED_AT];
        }

        $duplicateIds = $this->repo->payout->dedupeAtPayoutService($ids);

        foreach ($duplicateIds as $duplicateId)
        {
            unset($sourceData[$idKeyMap[$duplicateId]]);
        }

        return [$lastId, $lastCreatedAt];
    }

    protected function getDataForPayoutService($payouts)
    {
        $payoutServiceData                                                           = [];
        $payoutServiceData[$this->getPSTableName(self::PAYOUTS)]               = [];
        $payoutServiceData[$this->getPSTableName(self::REVERSALS)]             = [];
        $payoutServiceData[$this->getPSTableName(self::PAYOUT_LOGS)]           = [];
        $payoutServiceData[$this->getPSTableName(self::PAYOUT_DETAILS)]        = [];
        $payoutServiceData[$this->getPSTableName(self::PAYOUT_SOURCES)]        = [];
        $payoutServiceData[$this->getPSTableName(self::WORKFLOW_ENTITY_MAP)]   = [];
        $payoutServiceData[$this->getPSTableName(self::WORKFLOW_STATE_MAP)]   = [];
        $payoutServiceData[$this->getPSTableName(self::PAYOUT_STATUS_DETAILS)] = [];

        foreach ($payouts as $payout)
        {
            $payoutServiceData[$this->getPSTableName(self::PAYOUTS)][] =
                (new PayoutMigration)->getPayoutServicePayoutFromApiPayout($payout);

            $payoutServiceData[$this->getPSTableName(self::REVERSALS)] =
                array_merge(
                    $payoutServiceData[$this->getPSTableName(self::REVERSALS)],
                    (new ReversalMigration)->getPayoutServiceReversalForApiPayout($payout)
                );

            $payoutServiceData[$this->getPSTableName(self::PAYOUT_LOGS)] =
                array_merge(
                    $payoutServiceData[$this->getPSTableName(self::PAYOUT_LOGS)],
                    (new PayoutLogsMigration)->getPayoutServicePayoutLogsForApiPayout($payout)
                );

            $payoutServiceData[$this->getPSTableName(self::PAYOUT_SOURCES)] =
                array_merge(
                    $payoutServiceData[$this->getPSTableName(self::PAYOUT_SOURCES)],
                    (new PayoutSourceMigration)->getPayoutServicePayoutSourcesForApiPayout($payout)
                );

            $payoutServiceData[$this->getPSTableName(self::PAYOUT_DETAILS)] =
                array_merge(
                    $payoutServiceData[$this->getPSTableName(self::PAYOUT_DETAILS)],
                    (new PayoutDetailsMigration)->getPayoutServicePayoutDetailsForApiPayout($payout)
                );

            $payoutServiceWorkflowEntityMapForApiPayout = (new WorkflowEntityMapMigration)
                ->getPayoutServiceWorkflowEntityMapForApiPayout($payout);

            $payoutServiceData[$this->getPSTableName(self::WORKFLOW_ENTITY_MAP)] =
                array_merge(
                    $payoutServiceData[$this->getPSTableName(self::WORKFLOW_ENTITY_MAP)],
                    $payoutServiceWorkflowEntityMapForApiPayout
                );

            if (empty($payoutServiceWorkflowEntityMapForApiPayout) === false)
            {
                foreach ($payoutServiceWorkflowEntityMapForApiPayout as $WorkflowEntityMap) {
                    $workflowId = $WorkflowEntityMap[WorkflowEntityMapEntity::WORKFLOW_ID];

                    $payoutServiceData[$this->getPSTableName(self::WORKFLOW_STATE_MAP)] =
                        array_merge(
                            $payoutServiceData[$this->getPSTableName(self::WORKFLOW_STATE_MAP)],
                            (new WorkflowStateMapMigration())->getPayoutServiceWorkflowStateMapForApiPayout($workflowId)
                        );
                }
            }

            $payoutServiceData[$this->getPSTableName(self::PAYOUT_STATUS_DETAILS)] =
                array_merge(
                    $payoutServiceData[$this->getPSTableName(self::PAYOUT_STATUS_DETAILS)],
                    (new PayoutStatusDetailsMigration)->getPayoutServicePayoutStatusDetailsForApiPayout($payout)
                );
        }

        return $payoutServiceData;
    }
}
