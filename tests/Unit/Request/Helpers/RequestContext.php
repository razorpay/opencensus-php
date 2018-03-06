<?php

namespace RZP\Tests\Unit\Request\Helpers;

use RZP\Http\Throttle\HasRequestContext;

/**
 * Helper class that assists in unit testing protected/private
 * methods of HasRequestContext trait.
 */
class RequestContext
{
    use HasRequestContext { initRequestContextVars as public; }

    protected $isRunningUnitTests;
    protected $applications;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->isRunningUnitTests = $app->runningUnitTests();
        $this->applications       = $app['config']->get('applications');
    }

    public function __get(string $attribute)
    {
        return $this->$attribute;
    }
}
