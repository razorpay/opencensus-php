<?php

namespace RZP\Modules;

use App;

abstract class Base
{
    protected $app;

    protected $env;

    protected $mode;

    protected $trace;

    protected $repo;

    protected $merchant;

    /**
     * Base constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']) === true)
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->env = $this->app['env'];

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->merchant = $this->app['basicauth']->getMerchant();
    }
}
