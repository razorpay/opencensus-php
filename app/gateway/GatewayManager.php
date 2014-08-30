<?php

namespace Gateway;

use Config;
use EE\Exception;
use Gateway\Hdfc;

class GatewayManager extends \Illuminate\Support\Manager
{
    public function __construct()
    {
        $config = Config::get('gateway');

        $this->available = $config['available'];

        $this->defaultDriver = $config['default'];
    }

    public function createHdfcDriver()
    {
        return new Hdfc\Gateway();
    }

    public function createMockDriver()
    {
        return new Mock\Gateway();
    }

    public function createMockHdfcDriver()
    {
        return new MockHdfc\Gateway();
    }

    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }
}