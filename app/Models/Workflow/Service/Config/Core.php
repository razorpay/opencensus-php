<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Workflow\Service\Adapter\Constants;
use RZP\Models\Workflow\Service\Client;
use RZP\Exception\ServerErrorException;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    /** @var Client */
    protected $workflowServiceClient;

    public function __construct()
    {
        parent::__construct();

        $this->workflowServiceClient = new Client;
    }

    public function create(array $input): array
    {
        // todo: Delete old create flow
        $response = $this->workflowServiceClient->createConfig($input);

        $attributes = [
            Entity::ID          => $response[Entity::ID],
            Entity::CONFIG_ID   => $response[Entity::ID],
            Entity::CONFIG_TYPE => $response[Entity::TYPE],
            Entity::ENABLED     => $response[Entity::ENABLED] === "true",
        ];

        $workflowConfigEntity = (new Entity)->build($attributes);

        /** @var Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->findOrFailPublic($response[Entity::OWNER_ID]);

        $workflowConfigEntity->merchant()->associate($merchant);

        $workflowConfigEntity->org()->associate($merchant->org);

        $this->repo->saveOrFail($workflowConfigEntity);

        return $response;
    }

    public function createWorkflowConfig(array $input): array
    {
        // Duplicating code, since the old flow will be removed in future
        // todo: Remove this comment post removal of old flow

        // default config type is 'payout-approval'
        $input[Constants::CONFIG_TYPE] = array_pull($input, Constants::CONFIG_TYPE, Constants::PAYOUT_APPROVAL_CONFIG_TYPE);

        // Create config in workflow service
        $response = $this->workflowServiceClient->createConfigV2($input);

        $this->saveConfigId($response, $input[Constants::CONFIG_TYPE]);

        return $response;
    }

    public function updateWorkflowConfig(array $input): array
    {
        // default config type is 'payout-approval'
        $input[Constants::CONFIG_TYPE] = array_pull($input, Constants::CONFIG_TYPE, Constants::PAYOUT_APPROVAL_CONFIG_TYPE);

        // Update config in workflow service
        $response = $this->workflowServiceClient->updateConfigV2($input);

        $this->saveConfigId($response, $input[Constants::CONFIG_TYPE]);

        return $response;
    }

    public function deleteWorkflowConfig(array $input): array
    {
        // default config type is 'payout-approval'
        $input[Constants::CONFIG_TYPE] = array_pull($input, Constants::CONFIG_TYPE, Constants::PAYOUT_APPROVAL_CONFIG_TYPE);

        // Delete config in workflow service
        $response = $this->workflowServiceClient->deleteConfig($input);

        return $response;
    }

    /**
     * @param array $response
     * @param string $config_type
     */
    private function saveConfigId(array $response, string $config_type) {
        $attributes = [
            Entity::ID          => $response[Entity::ID],
            Entity::CONFIG_ID   => $response[Entity::ID],
            Entity::CONFIG_TYPE => $config_type !== null ? $config_type : $response[Entity::TYPE],
            Entity::ENABLED     => $response[Entity::ENABLED] === 'true',
        ];

        // Store config ID to MID mapping in API DB
        $workflowConfigEntity = (new Entity)->build($attributes);

        $merchant = $this->repo->merchant->findOrFailPublic($response[Entity::OWNER_ID]);

        $workflowConfigEntity->merchant()->associate($merchant);

        $workflowConfigEntity->org()->associate($merchant->org);

        $this->repo->saveOrFail($workflowConfigEntity);
    }

    /**
     * @param Entity $config
     * @param array $input
     * @return array
     * @throws ServerErrorException
     * @throws BadRequestValidationFailureException
     */
    public function update(Entity $config, array $input): array
    {
        $response = $this->workflowServiceClient->updateConfig($input);

        if (isset($response[Entity::ENABLED]) === true)
        {
            $enabled = $response[Entity::ENABLED] == "true";

            $config->setEnabled($enabled);
        }

        $this->repo->saveOrFail($config);

        return $response;
    }

    /**
     * @param string $id
     * @return array
     * @throws ServerErrorException
     */
    public function getViaWorkflowService(string $id): array
    {
        return $this->workflowServiceClient->getConfigById($id);
    }
}
