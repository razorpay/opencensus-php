<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use Request;
use Twirp\Context;
use Razorpay\Trace\Logger;

class BaseClient
{
    protected $app;

    /** @var Logger */
    protected $trace;

    protected $bvsConfig;

    protected $apiClientCtx;

    protected $httpClient;

    protected $host;

    const AUTHORIZATION_KEY = 'Authorization';

    const REQUEST_ID_KEY = 'X-Request-ID';

    const CLIENT_ID_KEY = 'X-Client-ID';

    const IS_SYNC = 'is_sync';

    protected $sync;

    /**
     * @var mixed|null
     */
    protected $merchant;

    function __construct($merchant = null,$sync = false)
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->merchant = $merchant;

        $this->sync = $sync;

        $this->bvsConfig = $app['config']['services.business_verification_service'];

        $this->host = $this->bvsConfig['host'];

        $this->httpClient = app('bvs_http_client');

        $auth = 'Basic ' . base64_encode($this->bvsConfig['user'] . ':' . $this->bvsConfig['password']);

        $headers = [
            self::AUTHORIZATION_KEY => $auth,
            self::REQUEST_ID_KEY    => Request::getTaskId(),
            self::CLIENT_ID_KEY     => $this->bvsConfig['client_id'],
            self::IS_SYNC           => ($sync?'true':'false')
        ];

        $this->apiClientCtx = Context::withHttpRequestHeaders([], $headers);
    }
}
