<?php

namespace Models\Base;

use App;

class Service
{
    /**
     * The application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

	public function __construct()
	{
		$this->app = App::getFacadeRoot();

        $this->mode = $this->app['basicauth']->getMode();

        $this->trace = $this->app['trace'];
	}

    public static function getNewInstance()
    {
        return new static;
    }
}