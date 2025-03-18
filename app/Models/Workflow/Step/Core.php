<?php

namespace RZP\Models\Workflow\Step;

use RZP\Constants\Mode;
use RZP\Models\Workflow;
use RZP\Models\Merchant;
use RZP\Models\Workflow\Base;
use RZP\Models\Merchant\RazorxTreatment;

class Core extends Base\Core
{
    const WORKFLOW_STEP_CREATED_CAC_ROLE = 'workflow_step_created_cac_role';

    public function create(array $input, Workflow\Entity $workflow)
    {
        $step = new Entity;

        $step->generateId();

        $step->build($input);

        $step->workflow()->associate($workflow);

        $isCacEnabled = false;

        if (empty($this->merchant) === false)
        {
            $isCacEnabled = $this->merchant->isCACEnabled();
        }

        Entity::setCacStatus($isCacEnabled);

        if ($isCacEnabled === true)
        {
            /**
             * Execution should never reach this point.
             * The oldest workflow with cac_role is from Dec-2023 and all such workflows seem to be bugged out as there are no workflow_steps,
             * workflow_actions or workflow_entity_map: Refer: PR#47129
             *
             * To minimise impact on other products that use these workflows, we will add a metric here and setup alerts to monitor usage.
             */
            $this->trace->count(self::WORKFLOW_STEP_CREATED_CAC_ROLE, [
                'workflow_id' => $workflow->getId(),
                'merchant_id' => empty($this->merchant) ? null : $this->merchant->getId(),
            ]);

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
