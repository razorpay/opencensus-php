<?php

namespace Models\Base;

use App;
use Services\SlackPoster;

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
	}

    public static function getNewInstance()
    {
        return new static;
    }
}
