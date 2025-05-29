<?php

namespace RZP\Models\DeviceDetail;

use App;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const USER_ID                = 'user_id';
    const APPSFLYER_ID           = 'appsflyer_id';
    /*
     * added for identifying mobile app mtu transactions
     */
    const SIGNUP_SOURCE          = 'signup_source';
    const SIGNUP_CAMPAIGN        = 'signup_campaign';

    const CREATED_AT             = 'created_at';
    const UPDATED_AT             = 'updated_at';
    const METADATA               = 'metadata';

    const MODULAR_WITHOUT_MKYC                            = 'modular_without_mkyc';
    const UDD_DECOMP_ONBOARDING_WORKFLOW_DETAILS_METADATA = 'udd_decomp_onboarding_workflow_details_metadata';

    protected $entity            = 'user_device_detail';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
        self::SIGNUP_SOURCE,
        self::SIGNUP_CAMPAIGN,
        self::METADATA
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::USER_ID,
        self::APPSFLYER_ID,
        self::SIGNUP_SOURCE,
        self::SIGNUP_CAMPAIGN,
        self::METADATA
    ];

    protected $casts = [
        self::METADATA => 'array',
    ];

    protected $defaults = [
        self::METADATA => []
    ];

    public function getAppsFlyerId()
    {
        return $this->getAttribute(self::APPSFLYER_ID);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getSignupSource()
    {
        return $this->getAttribute(self::SIGNUP_SOURCE);
    }

    public function getSignupCampaign()
    {
        return $this->getAttribute(self::SIGNUP_CAMPAIGN);
    }

    public function getMetaData()
    {
        return $this->getAttribute(self::METADATA);
    }

    /**
     * @throws ServerErrorException
     */
    public function getValueFromMetaData($key, &$fetchedFromOnboardingDetails = false, $transformToApiRes = true)
    {
        $app = App::getFacadeRoot();

        $metaData = $this->getAttribute(self::METADATA);

        $readingWorkflowDetailsFromCache = false;
        $readingWorkflowDetailsFromContext = false;
        $onboardingDetails = $app['request.ctx.v2']->merchantOnboardingDetails ?? null;
        $app['trace']->info(TraceCode::ONBOARDING_WORKFLOW_DETAILS_FROM_CONTEXT, [
            'onboarding_details' => $onboardingDetails,
        ]);
        if (!empty($onboardingDetails) === true)
        {
            $readingWorkflowDetailsFromContext = true;
        }

        if (empty($onboardingDetails) === true)
        {
            $onboardingWorkflowDetailsCacheKey = sprintf('%s_%s', self::UDD_DECOMP_ONBOARDING_WORKFLOW_DETAILS_METADATA, $this->getMerchantId());
            $onboardingDetails = $app['cache']->get($onboardingWorkflowDetailsCacheKey);
            $app['trace']->info(TraceCode::ONBOARDING_WORKFLOW_DETAILS_FROM_CACHE, [
                'onboarding_details' => $onboardingDetails,
            ]);
            $readingWorkflowDetailsFromCache = true;
        }


        $isPGOSReadForMetadataEnabled = false;
        $onlinePgIndiaMerchantVersion = "";
        $isPGEOToMOMerchant = false;

        if (empty($onboardingDetails) === true)
        {
            $properties = [
                'id'            => $this->getMerchantId(),
                'experiment_id' => $app['config']->get('app.pgos_read_for_metadata_enabled'),
            ];
            $isPGOSReadForMetadataEnabled = (new \RZP\Models\Merchant\Core())->isSplitzExperimentEnable($properties,'enable');
            if ($isPGOSReadForMetadataEnabled === false)
            {
                $isPGEOToMOMerchant = (new \RZP\Models\Merchant\Detail\Core())->getSplitzResponse($this->getMerchantId(), 'online_pg_india_merchant_version') === self::MODULAR_WITHOUT_MKYC;
            }
            $readingWorkflowDetailsFromCache = false;

            $app['trace']->info(TraceCode::ONBOARDING_WORKFLOW_DETAILS_NOT_PRESENT_IN_CACHE_OR_CONTEXT, [
                'online_pg_india_merchant_version' => $onlinePgIndiaMerchantVersion,
                'is_pgos_read_for_metadata_enabled' => $isPGOSReadForMetadataEnabled,
            ]);
        }

        $shouldUsePGOSOnboardingDetails = $isPGOSReadForMetadataEnabled || $isPGEOToMOMerchant || !empty($onboardingDetails);

        if ($shouldUsePGOSOnboardingDetails && ($key === Constants::WORKFLOW_DETAILS || $key === Constants::SERVICE)) {

            try
            {
                if (empty($onboardingDetails))
                {
                    $onboardingDetails = (new Core())->fetchOnboardingWorkflowDataFromPGOS($this->getMerchantId());
                    if (!empty($onboardingDetails) === true)
                    {
                        if ($isPGOSReadForMetadataEnabled === true)
                        {
                            $app['request.ctx.v2']->merchantOnboardingDetails = $onboardingDetails;
                            $app['trace']->info(TraceCode::SAVING_WOKFLOW_DETAILS_TO_CONTEXT, [
                                'onboarding_details' => $onboardingDetails,
                            ]);
                        }
                        if ($isPGEOToMOMerchant === true and !empty($onboardingDetails))
                        {
                            $app['cache']->put($onboardingWorkflowDetailsCacheKey, $onboardingDetails, 3600);
                            $app['trace']->info(TraceCode::SAVING_WOKFLOW_DETAILS_TO_CACHE, [
                                'onboarding_details' => $onboardingDetails,
                            ]);
                        }
                    }
                }
            }
            catch (\Throwable $e)
            {
                $app['trace']->error(TraceCode::GET_WORKFLOW_DETAILS_FROM_PGOS_ERROR, [
                    'error'  => $e->getMessage(),
                    'trace_code' => $e->getTraceAsString(),
                ]);
                throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::SERVER_ERROR);
            }

            if (!empty($onboardingDetails) && array_get($onboardingDetails, Constants::WORKFLOW_DETAILS_OWNER_SERVICE) === Constants::SERVICE_PGOS) {

                if ($key === Constants::WORKFLOW_DETAILS) {

                    if ($transformToApiRes === false && isset($onboardingDetails[Constants::WORKFLOW_DETAILS])) {

                        $fetchedFromOnboardingDetails = true;

                        $metaData[Constants::WORKFLOW_DETAILS] = $onboardingDetails[Constants::WORKFLOW_DETAILS];

                    } else {

                        $mergeRes = $this->mergeResponses($onboardingDetails, $metaData);

                        $metaData = $mergeRes[Constants::MERGED_OBJECT];

                        $fetchedFromOnboardingDetails = $mergeRes[Constants::FETCHED_FROM_ONBOARDING_DETAILS];
                    }

                } else {

                    $metaData[Constants::SERVICE] = Constants::SERVICE_PGOS;

                    $fetchedFromOnboardingDetails = true;
                }

            }
        }

        $app['trace']->info(TraceCode::GET_VALUE_FROM_METADATA, [
            'merchant_id'                     => $this->getMerchantId(),
            'metadata_key'                    => $key,
            'metaData'                        => $metaData,
            'fetchedFromOnboardingDetails'    => $fetchedFromOnboardingDetails,
            'readingWorkflowDetailsFromCache' => $readingWorkflowDetailsFromCache,
            'fetchedFromRequestContext'       => $readingWorkflowDetailsFromContext,
        ]);

        $metricData = [
            'route' => $app['request.ctx']->getRoute(),
            'source' => $fetchedFromOnboardingDetails ? "onboarding_details" : "user_device_detail",
            'key' => $key,
            'fetch_source' => $readingWorkflowDetailsFromContext ? 'context' : ($readingWorkflowDetailsFromCache ? 'cache' : 'default'),
        ];

        $app['trace']->count(Metric::FETCH_WORKFLOW_DETAILS_SUCCESS, $metricData);

        $value = null;

        if (empty($metaData) === false
            and array_key_exists($key, $metaData))
        {
            $value = $metaData[$key];
        }

        return $value;
    }

    public function isAssistedOnboardedMerchant(): bool
    {
        if ($this->getSignupCampaign() === Constants::ASSISTED_ONBOARDING || $this->getSignupCampaign() === Constants::PARTNER_ASSISTED_ONBOARDING)
        {
            return true;
        }

        return false;
    }

    private function mergeResponses(array $onboardingMetadata, array $uddMetadata): array
    {
        $mergedObject = $uddMetadata;

        if (isset($onboardingMetadata[Constants::WORKFLOW_DETAILS]) && is_array($onboardingMetadata[Constants::WORKFLOW_DETAILS])) {

            foreach ($onboardingMetadata[Constants::WORKFLOW_DETAILS] as $productKey => $value) {

                if (is_array($value)) {

                    $workflowTypeKey = $productKey . '_' . 'workflow_type';
                    $workflowVersionKey = $productKey . '_' . 'workflow_version';

                    $mergedObject[Constants::WORKFLOW_DETAILS][$workflowTypeKey] = !empty($value[Constants::WORKFLOW_TYPE]) ? $value[Constants::WORKFLOW_TYPE] : null;
                    $mergedObject[Constants::WORKFLOW_DETAILS][$workflowVersionKey] = !empty($value[Constants::VERSION_ID]) ? $value[Constants::VERSION_ID] : null;

                    return [
                        Constants::MERGED_OBJECT                     => $mergedObject,
                        Constants::FETCHED_FROM_ONBOARDING_DETAILS   => true
                    ];
                }
            }
        }

        return [
            Constants::MERGED_OBJECT                     => $mergedObject,
            Constants::FETCHED_FROM_ONBOARDING_DETAILS   => false
        ];
    }

}
