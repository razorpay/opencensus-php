<?php

namespace RZP\Models\Store;

use App;

class Base
{
    protected $redis;

    protected $trace;

    function __construct()
    {
        $app = App::getFacadeRoot();

        $this->redis = $app['redis'];

        $this->trace = $app['trace'];
    }
}
