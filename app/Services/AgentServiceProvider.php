<?php

namespace RZP\Services;

use Jenssegers\Agent;

class AgentServiceProvider extends Agent\AgentServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
}