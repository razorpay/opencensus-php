<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Service\StateMap\Entity;
use RZP\Models\Workflow\Client;

class Core extends Base\Core
{
    public function create(array $input): array
    {
        $stateMap = $this->repo->workflow_state_map->getByStateId($input[Entity::REQUEST_STATE_ID]);

        if ($stateMap !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_WORKFLOW_STATE_ID_INVALID,
                null,
                [
                    'id'     => $input[Entity::REQUEST_STATE_ID],
                ]
            );
        }

        $transformedInput = $this->inputTransform($input);

        /** @var Entity $config */
        $workflow = $this->repo->workflow_entity_map->getByWorkflowId($input[Entity::REQUEST_WORKFLOW_ID]);

        // todo: error handling

        $transformedInput[Entity::MERCHANT_ID] = $workflow->getMerchantId();

        $transformedInput[Entity::ORG_ID] = $workflow->getOrgId();

        $stateMapEntity = new Entity($transformedInput);

        $stateMapEntity->generateId();

        $org = $this->repo->org->findOrFailPublic($workflow->getOrgId());

        $merchant   = $this->repo->merchant->findOrFailPublic($workflow->getMerchantId());

        $stateMapEntity->org()->associate($org);

        $stateMapEntity->merchant()->associate($merchant);

        $this->repo->saveOrFail($stateMapEntity);

        return $stateMapEntity->toArrayPublic();
    }

    public function update(string $id, array $input): array
    {
        $stateMap = $this->repo->workflow_state_map->getByStateId($id);

        if ($stateMap === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_WORKFLOW_STATE_ID_INVALID,
                null,
                [
                    'id'     => $id,
                ]
            );
        }

        $stateMap->setStatus($input[Entity::REQUEST_STATUS]);

        $this->repo->saveOrFail($stateMap);

        return $stateMap->toArrayPublic();
    }

    private function inputTransform(array $input) : array
    {
        return [
            Entity::WORKFLOW_ID             => $input[Entity::REQUEST_WORKFLOW_ID],
            Entity::STATE_ID                => $input[Entity::REQUEST_STATE_ID],
            Entity::STATE_NAME              => $input[Entity::REQUEST_STATE_NAME],
            Entity::TYPE                    => $input[Entity::REQUEST_TYPE],
            Entity::GROUP_NAME              => $input[Entity::REQUEST_GROUP_NAME],
            Entity::STATUS                  => $input[Entity::REQUEST_STATUS],
            Entity::ACTOR_TYPE_KEY          => $input[Entity::REQUEST_RULES][Entity::REQUEST_ACTOR_PROPERTY_KEY],
            Entity::ACTOR_TYPE_VALUE        => $input[Entity::REQUEST_RULES][Entity::REQUEST_ACTOR_PROPERTY_VALUE],
        ];
    }
}
