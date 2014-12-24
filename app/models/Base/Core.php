<?php

namespace Models\Base;

use App;

class Core
{
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->trace = $this->app['trace'];
    }

    public function getEntityClass()
    {
        return get_namespace($this) . '\Entity';
    }

    public function getRepositoryClass()
    {
        return get_namespace($this) . '\Repository';
    }
}
