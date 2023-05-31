<?php

namespace RZP\Jobs\DCS;

use Razorpay\Trace\Logger as Trace;
use RZP\Base\RuntimeManager;
use RZP\Jobs\Job;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Services\Dcs\Features\Type;
use RZP\Trace\TraceCode;

class ValidateFeaturesAPIAndDCS extends Job
{
    protected $mode;

    public $timeout = 12000;

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
        RuntimeManager::setMemoryLimit('2048M');

        RuntimeManager::setTimeLimit($this->timeout);

        RuntimeManager::setMaxExecTime($this->timeout);

        $this->trace->info(TraceCode::DCS_VALIDATE_FEATURES_API_AND_DCS_JOB, [
            'input' => $this->input
        ]);

        try {
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
                $this->validateEntityIdsForFeature($dcsReadEnabledFeatures[Type::ORG], Type::ORG);
            }

            // Validate for Merchant features
            if (key_exists(Type::MERCHANT, $dcsReadEnabledFeatures) === true) {
                $this->validateEntityIdsForFeature($dcsReadEnabledFeatures[Type::MERCHANT], Type::MERCHANT);
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::VALIDATE_ENTITY_ID_API_DCS_FEATURE_JOB_FAILED
            );
        }
        finally
        {
            $this->delete();
        }
    }

    /**
     * @param $dcsReadEnabledFeatures
     * @param $entityType
     * @return void
     */
    public function validateEntityIdsForFeature($dcsReadEnabledFeatures, $entityType): void
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
                $response = $dcs->fetchEntityIdsByFeatureNameInChunks($feature, $dcsOffset, self::LIMIT, $this->mode);

                $dcsEntityIds = array_merge($dcsEntityIds, $response['enabled_ids']);

                $dcsOffset = $response['returned_offset'];

                if ($dcsOffset === 0 or $dcsOffset === '0' or empty($dcsOffset) === true) {
                    break;
                }
            }

            $apiDiff = array_diff($apiEntityIds, $dcsEntityIds);
            $dcsDiff = array_diff($dcsEntityIds, $apiEntityIds);

            $this->trace->info(TraceCode::DCS_VALIDATE_FEATURES_API_AND_DCS_JOB_RESULT, [
                'api_diff' => $apiDiff,
                'dcs_diff' => $dcsDiff
            ]);
        }
    }
}
