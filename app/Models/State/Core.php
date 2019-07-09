<?php

namespace RZP\Models\State;

use RZP\Models\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Models\Base\PublicEntity as PublicEntity;

class Core extends Base\Core
{
    /**
     * @param array        $input
     * @param PublicEntity $maker
     * @param PublicEntity $entity
     *
     * @return Entity $state
     */
    public function createForMakerAndEntity(array $input, PublicEntity $maker, PublicEntity $entity): Entity
    {
        $state = $this->create($input);

        $makerEntityName = $maker->getEntity();

        $state->$makerEntityName()->associate($maker);

        $state->entity()->associate($entity);

        $this->repo->saveOrFail($state);

        return $state;
    }

    /**
     * @param Action       $action
     * @param string       $state
     * @param PublicEntity $admin
     */
    public function changeActionState(Action $action, string $state, PublicEntity $admin)
    {
        $input = [
            Entity::NAME       => $state,
        ];

        $this->createForMakerAndEntity($input, $admin, $action);
    }

    /**
     * @param array $input
     *
     * @return Entity
     */
    protected function create(array $input): Entity
    {
        $state = new Entity;

        $state->build($input);

        return $state;
    }
}
