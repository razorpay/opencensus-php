<?php

namespace RZP\Models\P2p\Base\Traits;

use Razorpay\Trace\Logger;

use RZP\Constants\Mode;
use RZP\Constants\Environment;
use RZP\Base\RepositoryManager;
use RZP\Models\P2p\Base\Libraries;
use Illuminate\Contracts\Foundation\Application;

trait ApplicationTrait
{
    use ExceptionTrait;
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var Libraries\ArrayBag
     */
    protected $input;

    /**
     * Any class can either call boot function or
     * set application from their constructor
     */
    public function bootApplicationTrait()
    {
        $this->app = app();
    }

    /**
     * Initialize should only be called at entry point in a method
     *
     * @param string $action
     * @param array $input
     */
    protected function initializeApplicationTrait(string $action, array $input)
    {
        $this->action = $action;

        $this->input = $this->arrayBag($input);
    }

    protected function context(): Libraries\Context
    {
        return $this->app['p2p.ctx'];
    }

    protected function trace(): Logger
    {
        return $this->app['trace'];
    }

    protected function mode()
    {
        return $this->context()->getMode();
    }

    protected function environment()
    {
        return $this->app->environment();
    }

    protected function isProductionAndLive()
    {
        return (($this->mode() === Mode::LIVE) and
                ($this->environment() === Environment::PRODUCTION));
    }

    protected function isUnitTest()
    {
        return $this->app->runningUnitTests();
    }

    protected function repo(): RepositoryManager
    {
        return $this->app['repo'];
    }

    protected function arrayBag(array $input = []): Libraries\ArrayBag
    {
        return new Libraries\ArrayBag($input);
    }
}
