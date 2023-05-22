<?php

namespace RZP\Services\Dcs\Features;

use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Illuminate\Foundation\Application;
use Psr\Http\Client\NetworkExceptionInterface;
use RZP\Services\Dcs\Cache;
use Razorpay\Dcs\Client;
use Razorpay\Dcs\Config\UserCredentials;
use Razorpay\Dcs\Config\Config;
use Razorpay\Trace\Logger as Trace;
use RZP\Trace\TraceCode;

class Base
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    protected $razorx;

    protected $client;

    /**
     * @var Client
     */
    protected Client $testClient ;

    /**
     * @var Client
     */
    protected Client $liveClient ;

    /**
     * @var Cache
     */
    protected mixed $cache;

    /**
     * DCS Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->trace   = $app['trace'];

        $this->request = $app['request'];

        $this->auth    = $app['basicauth'];

        $this->config  = $app['config']->get('applications.dcs');

        $this->razorx = $app['razorx'];

        $this->cache = $this->app['cache'];

        $this->initializeClientWithMode(Mode::TEST);
        $this->initializeClientWithMode(Mode::LIVE);
    }

    public function initializeClientWithMode($mode): void
    {
        $creds  = new UserCredentials();
        $cache = new Cache();
        $config = new Config($cache);

        $creds->setUsername($this->config[$mode]['username'])
            ->setPassword($this->config[$mode]['password']);

        $config->setServerURL($this->config[$mode]['url'])
            ->setMock($this->config['mock'])
            ->setUserCreds($creds);
        $config->setMode($mode);

        if ($mode === Mode::LIVE)
        {
            $this->liveClient = new Client($config);
        }
        elseif($mode === Mode::TEST)
        {
            $this->testClient = new Client($config);
        }
    }

    /**
     * throws Server exception in case of request failures
     *
     * @param \Throwable $e
     * @param bool $throwException
     * @return void
     * @throws Exception\ServerErrorException
     */
    protected function throwServerRequestException(\Throwable $e,bool $throwException =  true): void
    {
        $errorCode = ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE;

        if ($e instanceof NetworkExceptionInterface)
        {
            $errorCode = ErrorCode::SERVER_ERROR_DCS_SERVICE_TIMEOUT;
        }

        $this->trace->traceException(
            $e,
            Trace::CRITICAL,
            TraceCode::SERVER_ERROR_DCS_SERVICE_FAILURE);
        if ($throwException === true)
        {
             throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
        }
    }


    public function client($mode): Client
    {
        if ($mode === Mode::LIVE)
        {
            return $this->liveClient;
        }

        return $this->testClient;
    }
}
