<?php

namespace Models\Base;

use App;
use RZP\Services\SlackPoster;

class Service
{
    use SlackPoster;

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
     * @var Models\Merchant\Entity
     */
    protected $merchant;

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

        $this->mode = $this->app['rzp.mode'];

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];
	}

    public static function getNewInstance()
    {
        return new static;
    }
}
