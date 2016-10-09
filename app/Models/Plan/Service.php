<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $plan = $this->core->create($input, $this->merchant);

        return $plan->toArrayPublic();
    }
}