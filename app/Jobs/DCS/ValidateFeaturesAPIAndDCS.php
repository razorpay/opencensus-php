<?php

namespace RZP\Jobs\DCS;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RuntimeManager;
use RZP\Jobs\Job;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Entity;
use RZP\Services\Dcs\Features\Type;
use RZP\Services\Dcs\Features\Utility;
use RZP\Trace\TraceCode;

class ValidateFeaturesAPIAndDCS extends Job
{
    protected $mode;

    public $timeout = 20000;

    const LIMIT = 500;
    protected $input;

    public function __construct($input, $mode)
    {
        parent::__construct($mode);
        $this->input = $input;
        $this->mode = $mode;
    }

    public function handle(): void
    {
        parent::handle();
        RuntimeManager::setMemoryLimit('4096M');

        RuntimeManager::setTimeLimit($this->timeout);

        RuntimeManager::setMaxExecTime($this->timeout);

        $this->trace->info(TraceCode::DCS_VALIDATE_FEATURES_API_AND_DCS_JOB, [
            'input' => $this->input
        ]);

        try
        {
            $assignApiDiffToDcs = isset($this->input['assign_api_diff_to_dcs']) === true ? $this->input['assign_api_diff_to_dcs'] : false;

            $assignDcsDiffToApi = isset($this->input['assign_dcs_diff_to_api']) === true ? $this->input['assign_api_diff_to_dcs'] : false;

            if (isset($this->input['feature_name']) === true)
            {
                $dcsReadEnabledFeatures = $this->input['feature_name'];
            }
            else
            {
                $adminService = new AdminService;

                $dcsReadEnabledFeatures = $adminService->getConfigKey(
                    ['key' => ConfigKey::DCS_READ_WHITELISTED_FEATURES]);
            }

            // Validate for Org features
            if (key_exists(Type::ORG, $dcsReadEnabledFeatures) === true) {
                $this->validateEntityIdsForFeature($dcsReadEnabledFeatures[Type::ORG], Type::ORG, $assignApiDiffToDcs, $assignDcsDiffToApi);
            }

            // Validate for Merchant features
            if (key_exists(Type::MERCHANT, $dcsReadEnabledFeatures) === true) {
                $this->validateEntityIdsForFeature($dcsReadEnabledFeatures[Type::MERCHANT], Type::MERCHANT, $assignApiDiffToDcs, $assignDcsDiffToApi);
            }

            // Validate for Merchant features
            if (key_exists(Type::APPLICATION, $dcsReadEnabledFeatures) === true) {
                $this->validateEntityIdsForFeature($dcsReadEnabledFeatures[Type::APPLICATION], Type::APPLICATION, $assignApiDiffToDcs, $assignDcsDiffToApi);
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DCS_VALIDATE_FEATURES_API_AND_DCS_JOB_FAILED
            );
        }
        finally
        {
            $this->delete();
        }
    }

    /**
     * @param array $dcsReadEnabledFeatures
     * @param string $entityType
     * @param bool $assignApiDiffToDcs
     * @param bool $assignDcsDiffToApi
     * @return void
     */
    public function validateEntityIdsForFeature(array $dcsReadEnabledFeatures, string $entityType, bool $assignApiDiffToDcs, bool $assignDcsDiffToApi): void
    {

        $dcs = App::getFacadeRoot()['dcs'];
        // Fetch entity ids for a feature from API DB
        foreach ($dcsReadEnabledFeatures as $feature => $readEnabledOn) {
            $offset = 0;
            $index = 0;

            $apiEntityIds = [];
            while (true) {
                $newEntityIds = $this->repoManager
                    ->feature
                    ->fetchEntityIdsWithFeatureInChunks($feature, $entityType, $offset, self::LIMIT);


                if (empty($newEntityIds) === true) {
                    break;
                }

                $apiEntityIds = array_merge($apiEntityIds, $newEntityIds);

                $index++;

                $offset = $index * self::LIMIT;
            }

            $dcsOffset = 0;

            $dcsEntityIds = [];
            while (true) {
                $apiFeatureName = $entityType . ":" .$feature;
                $response = $dcs->fetchEntityIdsByFeatureNameInChunksViaProxy($apiFeatureName, $dcsOffset, self::LIMIT, $this->mode);

                $dcsEntityIds = array_merge($dcsEntityIds, $response['enabled_ids']);

                $dcsOffset = $response['returned_offset'];

                if ($dcsOffset === 0 or $dcsOffset === '0' or empty($dcsOffset) === true) {
                    break;
                }
            }

            $apiDiff = array_diff($apiEntityIds, $dcsEntityIds);
            $dcsDiff = array_diff($dcsEntityIds, $apiEntityIds);

            $this->trace->info(TraceCode::DCS_VALIDATE_FEATURES_API_AND_DCS_JOB_RESULT, [
                'api_diff_size' => sizeof($apiDiff),
                'dcs_diff_size' => sizeof($dcsDiff),
            ]);

            if ($assignApiDiffToDcs === true)
            {
                // no need to check via client so hardcoding the variant.
                $variant = 'on_direct_dcs_rs';

                // if complete diff $apiDiff will be as ["id1", "id2"]
                // if partial diff then $apiDiff will be as ["1" => "id1", "2" => "id2" ]

                // Divide $apiDiff into batches of 1000
                $apiDiffChunks = array_chunk($apiDiff, 1000);

                foreach ($apiDiffChunks as $chunk) {
                    // Call assign dcs api for each entity in the current chunk
                    AssignMerchantFeatures::dispatch($this->mode, $variant, $feature, $entityType, $chunk);
                    // Log information for the current chunk
                    $this->trace->info(TraceCode::DCS_EDIT_FEATURE_SCHEDULED_FOR_MERCHANT_JOB_DISPATCHED, [
                        "entity_ids_size" => sizeof($chunk)
                    ]);

                }
            }

            if ($assignDcsDiffToApi === true)
            {
                $adminService = new AdminService;
                $key = Utility::getRandomPrefix() . '_' . ConfigKey::DCS_READ_WHITELISTED_FEATURES;
                $dcsReadEnabledFeatures = $adminService->getConfigKey(
                    ['key' => $key]);
                if (empty($dcsReadEnabledFeatures) === false)
                {
                    $dcsReadEnabledFeaturesByEntityType = $dcsReadEnabledFeatures[$entityType];
                    // Assign to API if read enable is true
                    if (isset($dcsReadEnabledFeaturesByEntityType[$feature]) === true)
                    {
                        $this->assignToAPI($dcsDiff, $entityType, $feature);
                    }
                    else
                    {
                        $this->removeFromDcs($dcsDiff, $feature, $entityType);
                    }
                }

            }
        }
    }

    /**
     * @param array $dcsDiff
     * @param string $entityType
     * @param int|string $feature
     * @return void
     */
    public function assignToAPI(array $dcsDiff, string $entityType, int|string $feature): void
    {
        $successfulMerchant = [];
        foreach ($dcsDiff as $key => $entityId)
        {
            $featureParam = [
                Entity::ENTITY_TYPE => $entityType,
                Entity::ENTITY_ID => $entityId,
                Entity::NAME => $feature,
            ];

            try
            {
                $feature = (new Entity)->build($featureParam);
                $this->repoManager->feature->addFeatureInAPi($feature);
                $successfulMerchant[] = $entityId;
            }
            catch (\Exception|\Throwable $e)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::DCS_FEATURE_API_SYNC_FAILED);
            }
        }
        $this->trace->info(TraceCode::DCS_FEATURE_API_SYNC_SUCCESSFUL, [
            "entity_id" => $successfulMerchant
        ]);
    }

    /**
     * @param array $dcsDiff
     * @param int|string $feature
     * @param string $entityType
     * @return void
     */
    public function removeFromDcs(array $dcsDiff, int|string $feature, string $entityType): void
    {
        $success = [];
        $fail = [];
        try
        {
            foreach ($dcsDiff as $key => $entityId)
            {
                $data = [
                    Entity::NAME => $feature,
                    Entity::ENTITY_ID => $entityId,
                    Entity::ENTITY_TYPE => $entityType,
                ];

                $entity = (new Entity)->build($data);
                $entity->setEntityId($entityId);
                $entity->setEntityType($entityType);
                app('dcs')->editFeature($entity, 'on_direct_dcs_rs', false, $this->mode);
                $success[] = $entityId;
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::DCS_EDIT_FEATURE_MERCHANT_JOB_FAILED,
                [

                ]);
            $fail[] = $entityId;
        }
        $this->trace->info(TraceCode::DCS_FEATURE_API_SYNC_SUCCESSFUL, [
            "success" => $success,
            "fail" => $fail
        ]);
    }
}
