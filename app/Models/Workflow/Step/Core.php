<?php

namespace RZP\Models\Workflow\Step;

use RZP\Error;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $step = new Entity;

        $step->generateId();

        $step->build($input);

        $allSteps = $step->workflow->steps;

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

        $minLevel = $this->getMinLevelFromSteps($allSteps);

        $options = [Entity::WORKFLOW_ID => [$step->workflow->getId()]];

        $this->validateExistingWorkflows($allPermissions, $minLevel, $options);

        $this->repo->saveOrFail($step);

        return $step;
    }
}
