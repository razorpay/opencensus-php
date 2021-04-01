<?php

namespace RZP\Models\Payment\Validation;

use App;

abstract class Base
{
    protected $app;

    protected $mode;

    protected $trace;

    protected $repo;

    protected $merchant;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    public abstract function processValidation($input);
}
