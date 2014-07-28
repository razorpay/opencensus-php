<?php

namespace Models\Pricing;

use Models\Base;
use Models\Pricing;

class Repository extends Base\Repository
{
    protected $entity = 'Pricing';

    public function getPricingPlan($id)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PLAN_ID, '=', $id)->get();
    }

    public function getPricingPlanRule($id)
    {
        $repo = $this->repo;

        $repo::findOrFailPublic($id);
    }

    public function deletePlanRule($id)
    {
        $repo = $this->repo;

        $repo::delete($id);
    }
}