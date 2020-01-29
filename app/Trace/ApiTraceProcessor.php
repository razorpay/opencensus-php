<?php

namespace RZP\Trace;

use App;
use Request;

class ApiTraceProcessor
{
    protected $app;

    //
    // This regex is copied from
    // https://adamcaudill.com/2011/10/20/masking-credit-cards-for-pci/
    //
    // Sample string: 'CCPAY.4000000000000002@icici'
    //
    const CCPAY_CARD_REGEX = "/CCPAY.(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|" .
                             "6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|" .
                             "[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})/";

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

        $this->scrubCardNumberViaCcPay($record);

        $this->overrideRequestAttributes($record);

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
        // If this is an exception, a stack key is present in the context array
        $stackPresent = isset($record['context']['stack']);

        if ($stackPresent === true)
        {
            $record['request']['route_name'] = optional($this->app['router'])->currentRouteName();
        }
    }

    protected function scrubCardNumberViaCcPay(& $record)
    {
        $context = $record['context'] ?? null;

        if (empty($context) === true)
        {
            return;
        }

        array_walk_recursive($context, function(& $item)
        {
            if (is_string($item) === true)
            {
                if (preg_match(self::CCPAY_CARD_REGEX, $item) === 1)
                {
                    $item = 'CARD_NUMBER_SCRUBBED';
                }
            }
        });

        $record['context'] = $context;
    }

    /**
     * So WebProcessor is pushed with request object available at the time as
     * part of framework's first set of things i.e. registering service
     * providers. After this http middleware are registered where
     * Fideloper\Proxy\TrustProxies (library) verifies and attaches x-forwarded-
     * headers of proxy server. That's it- tiny bad practice/miss causing issues.
     *
     * Refer: Razorpay\Trace\Processor\WebProcessor@getServerData.
     *
     * @param  array &$record
     * @return void
     */
    protected function overrideRequestAttributes(array &$record)
    {
        $record['request']['url'] = $this->app->request->getUri();
    }
}
