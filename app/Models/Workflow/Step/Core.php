<?php

namespace RZP\Models\Workflow\Step;

use RZP\Models\Workflow\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $step = new Entity;

        $step->generateId();

        $step->build($input);

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
