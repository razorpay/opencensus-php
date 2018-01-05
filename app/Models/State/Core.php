<?php

namespace RZP\Models\State;

use RZP\Models\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Workflow\Action\Entity as Action;
use RZP\Models\Base\PublicEntity as PublicEntity;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;

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
     * @param MerchantDetailEntity $merchantDetails
     * @param Entity $maker [Admin/Merchant Entity]
     *
     * @return Entity $state
     */
    public function createForActivation(
        array $input,
        MerchantDetailEntity $merchantDetails,
        PublicEntity $maker): Entity
    {
        $state = $this->create($input);

        $makerEntityName = $maker->getEntity();

        $state->$makerEntityName()->associate($maker);

        $state->entity()->associate($merchantDetails);

        $this->repo->saveOrFail($state);

        return $state;
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
