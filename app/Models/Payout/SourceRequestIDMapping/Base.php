<?php

namespace RZP\Models\Payout\SourceRequestIDMapping;

use App;

use Illuminate\Foundation\Application;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

class Base
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    protected $attributes = [];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }
    }
} 