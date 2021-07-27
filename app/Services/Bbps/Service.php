<?php

namespace RZP\Services\Bbps;

use RZP\Models\Merchant;

class Service
{
    protected $app;

    protected $mocked;

    protected $trace;

    protected $bbpsConfig;

    protected $bbpsProvider;

    public function __construct($app)
    {
        $this->app = $app;

        $this->bbpsConfig  = $app['config']->get('applications.bbps');

        $this->trace   = $app['trace'];

        $this->bbpsProvider = $this->bbpsConfig['provider'];
    }

    public function getIframeForDashboard(Merchant\Entity $merchant, $mode)
    {
        return $this->getImplementation()->getIframeForDashboard($merchant, $mode);
    }

    public function getImplementation()
    {
        $implementationPath  = 'Impl';

        $class = __NAMESPACE__ . '\\'.$implementationPath.'\\' . studly_case($this->bbpsProvider);

        return new $class($this->bbpsConfig[$this->bbpsProvider], $this->trace);
    }
}
