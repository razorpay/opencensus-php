<?php

class BaseController extends Controller
{
    public function __construct()
    {
        $this->app = \App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }
}