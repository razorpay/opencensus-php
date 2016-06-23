<?php

namespace Models\Emi;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Core extends Base\Core
{
    public function __construct()
    {
        $this->repo = new Repository;
    }

    public function addEmiPlan($input)
    {
        $emiPlan = (new Entity)->build($input);

        $this->repo->saveOrFail($emiPlan);

        return $emiPlan;
    }
}