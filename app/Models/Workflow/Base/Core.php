<?php

namespace RZP\Models\Workflow\Base;

use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Models\Workflow\Step;

class Core extends Base\Core
{
    protected function getMinLevelForWorkflow(Workflow\Entity $workflow)
    {
        $minLevel = PHP_INT_MAX;

        $steps = $workflow->steps;

        foreach ($steps as $step)
        {
            if ($step->getLevel() < $minLevel)
            {
                $minLevel = $step->getLevel();
            }
        }

        return $minLevel;
    }

    protected function getMinLevelFromSteps(array $steps)
    {
        $minLevel = PHP_INT_MAX;

        foreach ($steps as $step)
        {
            if ($step[Step\Entity::LEVEL] < $minLevel)
            {
                $minLevel = $step[Step\Entity::LEVEL];
            }
        }

        return $minLevel;
    }
}
