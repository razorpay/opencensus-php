<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    /**
     * @param array $input
     * @return Entity
     */
    public function create(array $input): Entity
    {
        /** @var Entity $config */
        $workflow = $this->repo->workflow_entity_map->getByWorkflowId($input[Entity::REQUEST_WORKFLOW_ID]);

        /** @var Merchant\Entity $config */
        $merchant = $this->repo->merchant->findOrFailPublic($workflow->getMerchantId());

        $attributes = [
            Entity::WORKFLOW_ID             => $input[Entity::REQUEST_WORKFLOW_ID],
            Entity::STATE_ID                => $input[Entity::REQUEST_STATE_ID],
            Entity::STATE_NAME              => $input[Entity::REQUEST_STATE_NAME],
            Entity::TYPE                    => $input[Entity::REQUEST_TYPE],
            Entity::GROUP_NAME              => $input[Entity::REQUEST_GROUP_NAME],
            Entity::STATUS                  => $input[Entity::REQUEST_STATUS],
            Entity::ACTOR_TYPE_KEY          => $input[Entity::REQUEST_RULES][Entity::REQUEST_ACTOR_PROPERTY_KEY],
            Entity::ACTOR_TYPE_VALUE        => $input[Entity::REQUEST_RULES][Entity::REQUEST_ACTOR_PROPERTY_VALUE],
        ];

        $stateMapEntity = (new Entity)->build($attributes);

        $stateMapEntity->merchant()->associate($merchant);

        $stateMapEntity->org()->associate($merchant->org);

        $this->repo->saveOrFail($stateMapEntity);

        return $stateMapEntity;
    }

    /**
     * @param Entity $stateMap
     * @param array $input
     * @return Entity
     */
    public function update(Entity $stateMap, array $input): Entity
    {
        $stateMap->setStatus($input[Entity::REQUEST_STATUS]);

        $this->repo->saveOrFail($stateMap);

        return $stateMap;
    }
}
