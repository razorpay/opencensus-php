<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;
use RZP\Models\Workflow\Client;

class Core extends Base\Core
{
    /** @var Client */
    protected $workflowClient;

    public function __construct()
    {
        parent::__construct();

        $this->workflowClient = new Client;
    }

    public function create(array $input): array
    {
        $response = $this->workflowClient->createConfig($input);

        $workflowConfigEntity = new Entity;

        $workflowConfigEntity->generateId();

        if (isset($response[Entity::ID]) === true)
        {
            $workflowConfigEntity->setConfigId($response[Entity::ID]);
        }

        if (isset($response[Entity::TYPE]) === true)
        {
            $workflowConfigEntity->setConfigType($response[Entity::TYPE]);
        }

        if (isset($response['owner_id']) === true)
        {
            $workflowConfigEntity->setMerchantId($response['owner_id']);
        }

        if (isset($response[Entity::ORG_ID]) === true)
        {
            $workflowConfigEntity->setOrgId($response[Entity::ORG_ID]);
        }

        if (isset($response[Entity::ENABLED]) === true)
        {
            $enabled = $response[Entity::ENABLED] == "true" ? true : false;

            $workflowConfigEntity->setEnabled($enabled);
        }

        $org = $this->repo->org->findOrFailPublic($response[Entity::ORG_ID]);

        $merchant   = $this->repo->merchant->findOrFailPublic($response[Entity::OWNER_ID]);

        $workflowConfigEntity->org()->associate($org);

        $workflowConfigEntity->merchant()->associate($merchant);

        $this->repo->saveOrFail($workflowConfigEntity);

        return $response;
    }

    public function update(Entity $config, array $input): array
    {
        $configResponse = $this->workflowClient->updateConfig($input);

        if (isset($configResponse[Entity::ENABLED]) === true)
        {
            $enabled = $configResponse[Entity::ENABLED] == "true" ? true : false;

            $config->setEnabled($enabled);
        }

        $this->repo->saveOrFail($config);

        return $configResponse;
    }

    public function get(string $id): array
    {
        $configResponse = $this->workflowClient->getConfigById($id);

        return $configResponse;
    }
}
