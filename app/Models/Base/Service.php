<?php

namespace RZP\Models\Base;

use App;
use RZP\Models\Merchant;

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

    /**
     * The merchant making the request.
     * If merchant isn't making the request, then
     * it's null
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * Repository manager instance
     * @var Base\RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var \RZP\Trace\Trace
     */
    protected $trace;

    /**
     * Slack Client instance
     * @var Maknz\Slack\Facades\Slack
     */
    protected $slack;

	public function __construct()
	{
		$this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->slack = $this->app['slack'];
	}

    public static function getNewInstance()
    {
        return new static;
    }
}
