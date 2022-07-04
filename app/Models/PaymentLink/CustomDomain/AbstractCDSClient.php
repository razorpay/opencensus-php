<?php

namespace RZP\Models\PaymentLink\CustomDomain;

use Twirp\Context;
use Razorpay\Trace\Logger;
use Illuminate\Support\Facades\App;

abstract class AbstractCDSClient
{
    /**
     * @var \Illuminate\Support\Facades\App
     */
    protected $app;

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * @var array
     */
    protected $cdsConfig;

    /**
     * @var array
     */
    protected $apiClientCtx;

    /**
     * @var \Buzz\Client\MultiCurl
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $host;

    const AUTHORIZATION_KEY = 'Authorization';

    const TASK_ID_KEY = 'X-Task-ID';

    function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->cdsConfig = $app['config']['services.custom_domain_service'];

        $this->host = $this->cdsConfig['host'];

        $this->httpClient = app('cds_http_client');

        $auth = 'Basic ' . base64_encode($this->cdsConfig['app_name'] . ':' . $this->cdsConfig['secret']);

        $headers = [
            self::AUTHORIZATION_KEY => $auth,
            self::TASK_ID_KEY       => \Request::getTaskId(),
        ];

        $this->apiClientCtx = Context::withHttpRequestHeaders([], $headers);
    }
}
