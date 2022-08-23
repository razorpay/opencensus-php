<?php

namespace RZP\Models\Workflow\Step;

use RZP\Constants\Mode;
use RZP\Models\Workflow;
use RZP\Models\Merchant;
use RZP\Models\Workflow\Base;

class Core extends Base\Core
{
    public function create(array $input, Workflow\Entity $workflow)
    {
        $step = new Entity;

        $step->generateId();

        $step->build($input);

        $step->workflow()->associate($workflow);

        $isCacEnabled = false;

        if (empty($this->merchant) === false)
        {
            $isCacEnabled = $this->app['razorx']->getTreatment($this->merchant->getId(),
                    Merchant\RazorxTreatment::RX_CUSTOM_ACCESS_CONTROL_ENABLED,
                    Mode::LIVE) === Workflow\Constants::ON;
        }

        Entity::setCacStatus($isCacEnabled);

        if ($isCacEnabled === true)
        {
            $role = $this->repo->roles->findOrFailPublic($input[Entity::ROLE_ID]);
        }
        else
        {
            $role = $this->repo->role->findOrFailPublic($input[Entity::ROLE_ID]);
        }

        $step->role()->associate($role);

        $workflow = $this->repo->workflow->fetchWorkflow($step);

        $allSteps = $workflow->steps;

        $allSteps = $allSteps->map(function ($step) {
                                return $step->toArrayPublic();
                             })
                             ->prepend($input)
                             ->toArray();

        $step->getValidator()->validateStepLevel($allSteps);

        $step->getValidator()->validateStepUniqueness($allSteps);

        $allPermissions = $step->workflow
                               ->permissions
                               ->map(function ($permission) {
                                 return $permission->getId();
                               })
                               ->toArray();

        $this->repo->saveOrFail($step);

        return $step;
    }
}
