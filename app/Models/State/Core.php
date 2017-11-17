<?php

namespace RZP\Models\State;

use RZP\Models\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Action\Entity as Action;

class Core extends Base\Core
{
    /**
     * @param array        $input
     * @param Admin\Entity $admin
     * @param  Action      $action
     *
     * @return Entity $state
     */
    public function createForWorkflowAction(array $input, Admin\Entity $admin, Action $action): Entity
    {
        $state = $this->create($input);

        $state->admin()->associate($admin);

        $state->entity()->associate($action);

        $this->repo->saveOrFail($state);

        return $state;
    }

    /**
     * @param Action       $action
     * @param string       $state
     * @param Admin\Entity $admin
     */
    public function changeActionState(
        Action $action,
        string $state,
        Admin\Entity $admin)
    {
        $input = [
            Entity::NAME       => $state,
        ];

        $this->createForWorkflowAction($input, $admin, $action);
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
