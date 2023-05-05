<?php

namespace RZP\Http\Request;

use App;
use \WpOrg\Requests\Hooks as Requests_Hooks;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;

class Hooks
{
    protected $app;

    protected $mode;

    protected $url;

    const TRACE_REQUEST_FEATURE    = 'trace_request_metric';

    public function __construct($url)
    {
        $this->app = App::getFacadeRoot();

        $this->mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $this->url = $url;
    }

    public function addCurlProperties(array &$options)
    {
        if (!isset($options['hooks']) === true) {
            $options['hooks'] = new Requests_Hooks();
        }

        $hooks = &$options['hooks'];

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        $variant = $options['show_trace'] ?? false;

        if ($variant === true)
        {
            $hooks->register('curl.after_request', [$this, 'traceCurlInfo']);
        }

        unset($options['show_trace']);
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }

    public function traceCurlInfo($headers, $info)
    {
    }
}
