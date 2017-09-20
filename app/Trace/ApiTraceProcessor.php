<?php

namespace RZP\Trace;

use App;
use Request;
use RZP\Exception;

class ApiTraceProcessor
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function __invoke(array $record)
    {
        $this->addMode($record);

        $this->addOAuthAttributes($record);

        $this->updateClientIp($record);

        $this->addMerchantId($record);

        $this->addDashboardHeaders($record);

        return $record;
    }

    protected function addMode(& $record)
    {
        $record['mode'] = $this->app['basicauth']->getMode();
    }

    protected function addOAuthAttributes(& $record)
    {
        $record['request']['access_token_id'] = $this->app['basicauth']->getAccessTokenId();

        $record['request']['oauth_client_id'] = $this->app['basicauth']->getOAuthClientId();
    }

    protected function updateClientIp(&$record)
    {
        $record['request']['client_ip'] = $this->app['request']->ip();
    }

    protected function addMerchantId(&$record)
    {
        $record['request']['merchant_id'] = $this->app['basicauth']->getMerchantId();
    }

    protected function addDashboardHeaders(&$record)
    {
        if ($this->app['basicauth']->isDashboardApp() === true)
        {
            $record['request'] += $this->app['basicauth']->getDashboardHeaders();
        }
    }
}
