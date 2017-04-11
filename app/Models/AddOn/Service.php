<?php

namespace RZP\Models\AddOn;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }
}
