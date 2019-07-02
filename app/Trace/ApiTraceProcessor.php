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

        $this->addPartnerAttributes($record);

        $this->updateClientIp($record);

        $this->addMerchantId($record);

        $this->addDashboardHeaders($record);

        $this->addRouteNameForExceptions($record);

        return $record;
    }

    protected function addMode(& $record)
    {
        $record['mode'] = $this->app['basicauth']->getMode();
    }

    protected function addOAuthAttributes(& $record)
    {
        $accessTokenId = $this->app['basicauth']->getAccessTokenId();

        if (empty($accessTokenId) === false)
        {
            $record['request']['access_token_id'] = $accessTokenId;
        }

        $oauthClientId = $this->app['basicauth']->getOAuthClientId();

        if (empty($oauthClientId) === false)
        {
            $record['request']['oauth_client_id'] = $oauthClientId;
        }
    }

    protected function addPartnerAttributes(& $record)
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        if (empty($partnerMerchantId) === false)
        {
            $record['request']['partner_merchant_id'] = $partnerMerchantId;
        }
    }

    protected function updateClientIp(& $record)
    {
        $record['request']['client_ip'] = $this->app['request']->ip();
    }

    protected function addMerchantId(& $record)
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

    protected function addRouteNameForExceptions(& $record)
    {
        if ($this->isExceptionRecord($record) === true)
        {
            $record['request']['route_name'] = $this->app['router']->currentRouteName();
        }
    }

    protected function isExceptionRecord(array $record): bool
    {
        if (isset($record['context']['class']))
        {
            $pos = strpos($record['context']['class'], 'RZP\Exception');

            if ($pos === 0)
            {
                return true;
            }
        }

        return false;
    }
}
