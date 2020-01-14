<?php

namespace RZP\Http\Request;

use App;
use Requests_Hooks;

use RZP\Trace\TraceCode;

class Hooks
{
    protected $app;

    protected $mode;

    protected $url;

    const TRACE_REQUEST_FEATURE    = 'trace_request_metric';

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'];
    }

    public function addCurlProperties(string $url, array $options)
    {
        $this->url = $url;

        if (!isset($options['hooks']) === true) {
            $options['hooks'] = new Requests_Hooks();
        }

        $hooks = $options['hooks'];

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        $variant = $this->app->razorx->getTreatment('10000000000000', self::TRACE_REQUEST_FEATURE, $this->mode);

        if ($variant === 'on')
        {
            $hooks->register('curl.after_request', [$this, 'traceCurlInfo']);
        }
    }

    protected function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    protected function traceCurlInfo($headers, $info)
    {
        $this->app['trace']->info(TraceCode::TRACE_REQUEST_METRIC,[
            'url'                => $this->url,
            'total_time'         => $info['total_time'],
            'connect_time'       => $info['connect_time'],
            'redirect_time'      => $info['redirect_time'],
            'namelookup_time'    => $info['namelookup_time'],
            'pretransfer_time'   => $info['pretransfer_time'],
            'starttransfer_time' => $info['starttransfer_time'],
            'primary_ip'         => $info['primary_ip'] ?? 'nil',
        ]);
    }
}