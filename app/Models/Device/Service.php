<?php

namespace RZP\Models\Device;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create(array $input)
    {
        $device = $this->core->create($input);

        return $device->toArrayPublic();
    }

    public function verifyAndGetToken(array $input)
    {
        $device = $this->core->verifyAndGetToken($input);

        return $device->toArrayPublic();
    }
}
