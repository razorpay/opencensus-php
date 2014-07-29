<?php

namespace Models\Pricing;

use Models\Base;
use Models\Pricing;
use EE\Exception;
use EE\Error\ErrorCode;

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
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $plan;
    }

    public function getPricingPlans()
    {
        $repo = $this->repo;

        return $repo::orderBy(Entity::ID, 'desc')->take(10)->get();
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