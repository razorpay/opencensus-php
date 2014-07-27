<?php

namespace Models\Pricing;

use Models\Base;
use Models\Pricing;

class Repository extends Base\Repository
{
    protected $entity = 'Pricing';

    public function getPlan($id)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PLAN_ID, '=', $id)->findOrFailPublic();
    }

    public function getPlanRule($id)
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