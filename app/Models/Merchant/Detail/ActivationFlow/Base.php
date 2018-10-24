<?php

namespace RZP\Models\Merchant\Detail\ActivationFlow;

use App;

class Base
{
    /**
     * @var
     */
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }
}
