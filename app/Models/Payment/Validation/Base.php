<?php

namespace RZP\Models\Payment\Validation;

use App;

abstract class Base
{
    protected $app;

    protected $mode;

    protected $trace;

    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];
    }

    public abstract function processValidation($input);
}
