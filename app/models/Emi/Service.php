<?php

namespace Models\Emi;

use Models\Base;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Repository;
    }

    public function all()
    {
        $emiPlans = $this->repo->getAllEmiPlans();

        return $emiPlans->toArrayPublic();
    }

    public function fetch($id)
    {
        $emiPlans = $this->repo->findOrFail($id);

        return $emiPlans->toArrayPublic();
    }

    public function addEmiPlan(array $input)
    {
        $emiPlan = (new Core)->addEmiPlan($input);

        return $emiPlan->toArrayPublic();
    }
}