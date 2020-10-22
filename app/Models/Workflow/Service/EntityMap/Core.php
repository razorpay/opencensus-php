<?php

namespace RZP\Models\Workflow\Service\EntityMap;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input, Base\PublicEntity $entity)
    {
        $attributes = [
            Entity::WORKFLOW_ID => $input[Entity::WORKFLOW_ID],
            Entity::CONFIG_ID   => $input[Entity::CONFIG_ID],
            Entity::ENTITY_ID   => $entity->getId(),
            Entity::ENTITY_TYPE => $entity->getEntityName(),
        ];

        $workflowEntityMap = (new Entity)->build($attributes);

        $workflowEntityMap->merchant()->associate($entity->merchant);
        $workflowEntityMap->org()->associate($entity->merchant->org);

        $this->repo->saveOrFail($workflowEntityMap);

        return $workflowEntityMap;
    }
}
