<?php

namespace RZP\Models\Workflow\Service\Config;

use RZP\Models\Base;
use RZP\Models\Merchant;
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
