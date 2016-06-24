<?php

namespace Models\Base;

use App;

class Core
{
    /**
     * The application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var Base\RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace\Trace
     */
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];
    }
}
