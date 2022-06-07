<?php

namespace RZP\Models\AccessPolicyAuthzRolesMap;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $entityRepo;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function createMap(array $input)
    {
        $this->core->create($input);
    }
}
