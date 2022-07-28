<?php

namespace RZP\Services;

use RZP\Trace\TraceCode;
use GuzzleHttp\Client as HttpClient;

class AppsflyerClient
{
    protected $app;

    protected $trace;

    protected $config;

    protected $urlPatternOrganicUninstall;

    protected $urlPatternNonOrganicUninstall;

    protected $urlPatternOrganicInstall;

    protected $urlPatternNonOrganicInstall;

    protected $timezone;

    protected $maximumRows;

    const NON_ORGANIC_UNINSTALL_EVENT_URL_PATTERN   = '/export/com.razorpay.payments.app/uninstall_events_report/v5';

    const ORGANIC_UNINSTALL_EVENT_URL_PATTERN       = '/export/com.razorpay.payments.app/organic_uninstall_events_report/v5';

    const NON_ORGANIC_INSTALL_EVENT_URL_PATTERN     = '/export/com.razorpay.payments.app/installs_report/v5';

    const ORGANIC_INSTALL_EVENT_URL_PATTERN         = '/export/com.razorpay.payments.app/organic_installs_report/v5';

    const REQUEST_TIMEOUT = 20;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];

        $this->urlPatternNonOrganicUninstall = self::NON_ORGANIC_UNINSTALL_EVENT_URL_PATTERN;

        $this->urlPatternOrganicUninstall    = self::ORGANIC_UNINSTALL_EVENT_URL_PATTERN;

        $this->urlPatternNonOrganicInstall   = self::NON_ORGANIC_INSTALL_EVENT_URL_PATTERN;

        $this->urlPatternOrganicInstall      = self::ORGANIC_INSTALL_EVENT_URL_PATTERN;

        $this->config = $this->app['config']->get('services.appsflyer');

        $this->timezone = 'Asia%2fKolkata';

        $this->maximumRows = '200000';
    }

    //date should be in format 'yyyy-mm-dd'
    public function getOrganicUninstallEvent(string $from, string $to)
    {
        try
        {
            $urlParams = [
                'timezone' => $this->timezone,
                'from' => $from,
                'to' => $to,
                'maximum_rows' => $this->maximumRows,
                'url_pattern' => $this->urlPatternOrganicUninstall
            ];

            return $this->buildRequestAndSend($urlParams);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::APPSFLYER_GET_EVENT_DETAILS_FAILURE, [
                'type'          => 'organic uninstall'
            ]);
        }
    }

    public function getNonOrganicUninstallEvent(string $from, string $to)
    {
        try
        {
            $urlParams = [
                'timezone' => $this->timezone,
                'from' => $from,
                'to' => $to,
                'maximum_rows' => $this->maximumRows,
                'url_pattern' => $this->urlPatternNonOrganicUninstall
            ];

            return $this->buildRequestAndSend($urlParams);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::APPSFLYER_GET_EVENT_DETAILS_FAILURE, [
                'type'          => 'organic uninstall'
            ]);
        }
    }

    //date should be in format 'yyyy-mm-dd'
    public function getOrganicInstallEvent(string $from, string $to)
    {
        try
        {
            $urlParams = [
                'timezone' => $this->timezone,
                'from' => $from,
                'to' => $to,
                'maximum_rows' => $this->maximumRows,
                'url_pattern' => $this->urlPatternOrganicInstall
            ];

            return $this->buildRequestAndSend($urlParams);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::APPSFLYER_GET_EVENT_DETAILS_FAILURE, [
                'type'          => 'organic install'
            ]);
        }
    }

    public function getNonOrganicInstallEvent(string $from, string $to)
    {
        try
        {
            $urlParams = [
                'timezone' => $this->timezone,
                'from' => $from,
                'to' => $to,
                'maximum_rows' => $this->maximumRows,
                'url_pattern' => $this->urlPatternNonOrganicInstall
            ];

            return $this->buildRequestAndSend($urlParams);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::APPSFLYER_GET_EVENT_DETAILS_FAILURE, [
                'type'          => 'non organic install'
            ]);
        }
    }

    //date should be in format 'yyyy-mm-dd'
    public function getOrganicAndNonOrganicUninstallEvents($from, $to): array
    {
        return [
            $this->getOrganicUninstallEvent($from, $to),
            $this->getNonOrganicUninstallEvent($from, $to)
        ];
    }

    public function getOrganicAndNonOrganicInstallEvents($from, $to): array
    {
        return [
            $this->getOrganicInstallEvent($from, $to),
            $this->getNonOrganicInstallEvent($from, $to)
        ];
    }

    public function buildRequestAndSend($urlParams)
    {
        try
        {
            $apiToken = $this->config['auth']['read_key'];

            $urlPattern = $urlParams['url_pattern'];
            unset($urlParams['url_pattern']);

            $url = $this->config['url'] .  $urlPattern . '?api_token=' . $apiToken;

            foreach ($urlParams as $attr => $value) {
                $url .= '&' . $attr . '=' . $value;
            }

            $client = new HttpClient();

            return $client->request('get', $url, [
                'timeout'   => self::REQUEST_TIMEOUT
            ]);
        }
        catch (\Throwable $e)
        {
            $errorContext = [
                'class'     => get_class($this),
                'message'   => $e->getMessage(),
                'type'      => 'appsflyer'
            ];

            $this->trace->error(TraceCode::EVENT_GET_FAILED, $errorContext);
        }
    }
}
