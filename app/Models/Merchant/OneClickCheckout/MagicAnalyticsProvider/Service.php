<?php

namespace RZP\Models\Merchant\OneClickCheckout\MagicAnalyticsProvider;

use Razorpay\Trace\Logger as Trace;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Service
{
    protected $app;

    const ANALYTICS_EVENTS_API = 'analytics_events_api';
    const ANALYTICS_EVENTS_CONFIG_API = 'analytics_events_config_api';
    const PATH                 = "path";

    const PARAMS = [
        self::ANALYTICS_EVENTS_API  =>   [
            self::PATH   => 'v1/magic/analytics/events',
        ],
        self::ANALYTICS_EVENTS_CONFIG_API => [
            self::PATH => 'v1/magic/analytics/ad_partner/configs?merchant_id=',
        ]
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }

    public function triggerEvent($input, $headers)
    {
        $params = self::PARAMS[self::ANALYTICS_EVENTS_API];

        try
        {
            $this->app['integration_service_client']->sendRequest($params[self::PATH], Requests::POST, $input, $headers);
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAGIC_ANALYTICS_TRIGGER_ANALYTIC_EVENT_FAILED,
                []
            );

            $this->app['trace']->count(TraceCode::MAGIC_ANALYTICS_TRIGGER_ANALYTIC_EVENT_FAILED, ['event_type' => $input['event_type']]);
            return [];
        }
    }

    public function getMagicAnalyticsBEConfigs($merchant_id): array
    {
        $url = self::PARAMS[self::ANALYTICS_EVENTS_CONFIG_API][self::PATH] . $merchant_id;

        try
        {
            $response = $this->app['integration_service_client']->sendRequest($url, Requests::GET);

            return $response;
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAGIC_ANALYTICS_CONFIG_GET_BE_CONFIGS_ERROR,
                []
            );

            $this->app['trace']->count(TraceCode::MAGIC_ANALYTICS_CONFIG_GET_BE_CONFIGS_ERROR);
            throw $e;
        }
    }

    public function toggleBEGAAnalytics($merchant_id, $value)
    {
        $url = self::PARAMS[self::ANALYTICS_EVENTS_CONFIG_API][self::PATH] . $merchant_id;

        $input = [
            'provider_type' => 'google_universal_analytics',
            'be_ga_analytics' => $value,
        ];

        try
        {
            $this->app['integration_service_client']->sendRequest($url, Requests::POST, $input);
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAGIC_ANALYTICS_CONFIG_BE_GA_ANALYTICS_FAILED,
                []
            );

            $this->app['trace']->count(TraceCode::MAGIC_ANALYTICS_CONFIG_BE_GA_ANALYTICS_FAILED);

            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    public function toggleBEFbAnalytics($merchant_id, $value): void
    {
        $url = self::PARAMS[self::ANALYTICS_EVENTS_CONFIG_API][self::PATH] . $merchant_id;

        $input = [
            'provider_type' => 'fb_analytics',
            'one_cc_be_fb_analytics' => $value,
        ];

        try
        {
            $this->app['integration_service_client']->sendRequest($url, Requests::POST, $input);
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAGIC_ANALYTICS_CONFIG_BE_FB_ANALYTICS_FAILED,
                []
            );

            $this->app['trace']->count(TraceCode::MAGIC_ANALYTICS_CONFIG_BE_FB_ANALYTICS_FAILED);

            throw $e;
        }
    }

}
