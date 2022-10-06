<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\User as User;
use RZP\Constants as Constants;
use RZP\Models\Feature as Feature;
use RZP\Models\Workflow\Service\Config;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Workflow\Service\Adapter\Constants as WorkflowConstants;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new Config\Core;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException|Exception\ServerErrorException
     */
    public function create(array $input)
    {
        $merchantId = $input[Entity::CONFIG][Entity::OWNER_ID];
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $validator = new Validator;
        $validator->validateInput(Validator::WORKFLOW_CONFIG_CREATE, $input[Entity::CONFIG]);
        $validator->validateForNoPendingPayouts($merchant);
        $validator->validateForNoPendingPayoutLinks($merchant);
        $validator->validateOrgId($input[Entity::CONFIG], $merchant);

        return $this->core->create($input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function update(array $input)
    {
        $merchantId = $input[Entity::OWNER_ID];
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $validator = new Validator;
        $validator->validateInput(Validator::WORKFLOW_CONFIG_UPDATE, $input);
        $validator->validateForNoPendingPayouts($merchant);
        $validator->validateForNoPendingPayoutLinks($merchant);

        /** @var Entity $config */
        $config = $this->repo->workflow_config->getByConfigId($input[Entity::ID]);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                $input
            );
        }

        return $this->core->update($config, $input);
    }

    /**
     * @param string $id
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function get(string $id)
    {
        /** @var Entity $config */
        $config = $this->repo->workflow_config->getByConfigId($id);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                $id
            );
        }

        return $this->core->getViaWorkflowService($config->getConfigId());
    }

    /**
     * @param array $input
     * @return array
     *
     * createWorkflowConfig verifies the OTP and proxies the request to workflow service
     * workflow service converts the given input to workflow config, stores and returns the response
     */
    public function createWorkflowConfig(array $input)
    {
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            // Verify OTP
            (new User\Core)->verifyOtp($input,
                $this->app['basicauth']->getMerchant(),
                $this->app['basicauth']->getUser(),
                $this->app['basicauth']->getMode() === Constants\Mode::TEST);
        }

        $workflowInput = $this->generateWorkflowInput($input);

        $workflowResponse = $this->core->createWorkflowConfig($workflowInput);

        $this->enablePayoutWorkflowFeatureIfNotEnabled();

        return $workflowResponse;
    }

    /**
     * @param array $input
     * @return array
     *
     * updateWorkflowConfig verifies the OTP and proxies the request to workflow service
     */
    public function updateWorkflowConfig(array $input)
    {
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            // Verify OTP
            (new User\Core)->verifyOtp($input,
                $this->app['basicauth']->getMerchant(),
                $this->app['basicauth']->getUser(),
                $this->app['basicauth']->getMode() === Constants\Mode::TEST);

        }

        $workflowInput = $this->generateWorkflowInput($input);

        $workflowResponse = $this->core->updateWorkflowConfig($workflowInput);

        $this->enablePayoutWorkflowFeatureIfNotEnabled();

        return $workflowResponse;
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     *
     * deleteWorkflowConfig verifies the OTP and proxies the request to workflow service
     * Here we disable the config in workflow service and then disable the feature in API service
     */
    public function deleteWorkflowConfig(array $input)
    {
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            // Verify OTP
            (new User\Core)->verifyOtp($input,
                $this->app['basicauth']->getMerchant(),
                $this->app['basicauth']->getUser(),
                $this->app['basicauth']->getMode() === Constants\Mode::TEST);

        }

        $workflowInput = $this->generateWorkflowInput($input);

        $merchant = $this->app['basicauth']->getMerchant();

        $merchantId = $this->app['basicauth']->getMerchantId();

        if ($merchant->isFeatureEnabled(FeatureConstants::PAYOUT_WORKFLOWS) === true)
        {
            (new Feature\Service)->deleteEntityFeature(Feature\Type::ACCOUNTS, $merchantId, Feature\Constants::PAYOUT_WORKFLOWS, [Feature\Entity::SHOULD_SYNC => true]);
        }

        return $this->core->deleteWorkflowConfig($workflowInput);
    }

    public function enablePayoutWorkflowFeatureIfNotEnabled()
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $merchantId = $this->app['basicauth']->getMerchantId();

        if ($merchant->isFeatureEnabled(FeatureConstants::PAYOUT_WORKFLOWS) == false)
        {
            $featureInput = [
                Feature\Entity::ENTITY_TYPE => Feature\Constants::MERCHANT,
                Feature\Entity::ENTITY_ID => $merchantId,
                Feature\Entity::NAME => Feature\Constants::PAYOUT_WORKFLOWS,
            ];

            try
            {
                (new Feature\Core)->create($featureInput, true);
            }
            catch (Exception\BadRequestException | Exception\BadRequestValidationFailureException | Exception\ServerErrorException $e)
            {
                $this->trace->info(TraceCode::SELF_SERVE_WORKFLOW_PAYOUT_FEATURE_CREATE_FAILED,
                    [
                        'feature_input' => $featureInput,
                        'exception' => $e
                    ]);
            }
        }
    }

    /**
     * @param array $input
     * @return array
     *
     * bulkCreateWorkflowConfig, for each MID
     * 1. creates payout-approval workflow config
     * 2. creates icici-payout-approval workflow config
     * 3. enable payout_workflows feature for the MID
     */
    public function bulkCreateWorkflowConfig(array $input)
    {
        $this->trace->info(TraceCode::WORKFLOW_CONFIG_BULK_CREATE_REQUEST,
            [
                'input' => $input,
            ]);

        (new Validator)->validateInput(Validator::WORKFLOW_CONFIG_BULK_CREATE, $input);

        $merchantIds = $input[Entity::MERCHANT_IDS];

        $successMids = [];

        $failedMids = [];

        foreach ($merchantIds as $merchantId)
        {
            $merchant = $this->repo->merchant->getMerchant($merchantId);

            // Mark the MID as failed if workflow feature already enabled
            // This is to prevent both duplicate input MIDs and already workflow enabled MIDs
            if ($merchant->isFeatureEnabled(FeatureConstants::PAYOUT_WORKFLOWS) == true)
            {
                array_push($failedMids, $merchantId);

                continue;
            }

            try
            {
                // create payout-approval workflow config
                $workflowInput = $this->getBasicTemplatePayload($merchantId, WorkflowConstants::PAYOUT_APPROVAL_CONFIG_TYPE);

                $this->core->createWorkflowConfig($workflowInput);

                // create icici-payout-approval workflow config
                $workflowInput = $this->getBasicTemplatePayload($merchantId, WorkflowConstants::ICICI_PAYOUT_APPROVAL_TYPE);

                $this->core->createWorkflowConfig($workflowInput);

                // enable payout_workflows feature for the MID
                $featureInput = [
                    Feature\Entity::ENTITY_TYPE => Feature\Constants::MERCHANT,
                    Feature\Entity::ENTITY_ID => $merchantId,
                    Feature\Entity::NAME => Feature\Constants::PAYOUT_WORKFLOWS,
                ];

                (new Feature\Core)->create($featureInput, true);

                array_push($successMids, $merchantId);
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::WORKFLOW_CONFIG_BULK_CREATE_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'exception' => $e
                    ]);

                array_push($failedMids, $merchantId);
            }
        }

        return [
            "total_mids" => count($merchantIds),
            "success_mids" => $successMids,
            "failed_mids" => $failedMids
        ];
    }

    public function getBasicTemplatePayload(string $merchantId, string $configType)
    {
        $configTemplate = json_decode('{
            "config_template": [{
                "range": "1-20000000000",
                "steps": [{
                    "step": "1",
                    "op": "OR",
                    "roles": [{
                        "role_name": "Owner",
                        "role_id": "owner",
                        "approval_count": "1"
                    }]
                }]
            }]
        }', true);

        $configTemplate[Entity::OWNER_ID] = $merchantId;

        $configTemplate[WorkflowConstants::CONFIG_TYPE] = $configType;

        return $configTemplate;
    }

    /**
     * @param array $input
     * @return array
     *
     * generateWorkflowInput generates input for workflow service
     */
    private function generateWorkflowInput(array $input)
    {
        // Remove OTP, Token and Action from the input
        $workflowInput = array_except($input, ['otp', 'token', 'action']);

        // Get MID
        $merchantId = $this->app['basicauth']->getMerchantId();

        $workflowInput['owner_id'] = $merchantId;

        return $workflowInput;
    }

    /**
     * @param string $configType
     * @param string $merchantId
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function getConfigByType(string $configType, string $merchantId)
    {
        $config = $this->repo->workflow_config->getByConfigTypeAndMerchantId($configType, $merchantId);

        if ($config === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_WORKFLOW_CONFIG_ID_INVALID,
                null,
                ['merchant_id' => $merchantId, 'config_type' => $configType]);
        }

        return $this->core->getViaWorkflowService($config->getConfigId());
    }
}
