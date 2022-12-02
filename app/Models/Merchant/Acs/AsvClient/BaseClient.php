<?php

namespace RZP\Models\Merchant\Acs\AsvClient;

use App;
use Razorpay\Trace\Logger;

class BaseClient
{
    protected $app;

    /** @var Logger */
    protected $trace;

    protected $asvConfig;

    protected $asvClientCtx;

    protected $httpClient;

    protected $host;

    protected $headers;


    function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];

        $this->asvConfig = $app[Constant::CONFIG][Constant::ACCOUNT_SERVICE];

        $this->host = $this->asvConfig[Constant::HOST];

        $this->httpClient = app(Constant::ASV_HTTP_CLIENT);

        $auth = 'Basic ' . base64_encode($this->asvConfig[Constant::USER] . ':' . $this->asvConfig[Constant::PASSWORD]);

        $this->headers = [
            Constant::AUTHORIZATION_KEY => $auth,
            Constant::X_TASK_ID => $this->app['request']->getTaskId()
        ];
    }

    public function getHost() {
        return $this->host;
    }

    public function getHttpClient() {
        return $this->httpClient;
    }
}
