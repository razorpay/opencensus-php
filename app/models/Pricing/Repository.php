<?php

namespace Models\Pricing;

use Models\Base;
use Models\Pricing;
use EE\Exception;

class Repository extends Base\Repository
{
    protected $entity = 'Pricing';

    public function getPricingPlanById($id)
    {
        $repo = $this->repo;

        $plan = $repo::where(Entity::PLAN_ID, '=', $id)
                     ->orderBy(Entity::ID, 'desc')
                     ->get();

        if ($plan->count() === 0)
        {
            throw new Exception\BadRequestException($id . ' not found');
        }

        return $plan;
    }

    public function getPricingPlans()
    {
        $repo = $this->repo;

        return $repo::take(10)->get();
    }

    public function getPricingPlanByName($name)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PLAN_NAME, '=', $name)
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
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