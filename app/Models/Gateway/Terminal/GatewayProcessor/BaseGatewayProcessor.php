<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor;

use App;
use Illuminate\Support\Facades\Redis;

abstract class BaseGatewayProcessor
{

    protected $app;

    protected $redis;

    protected $gateway;

    protected $repo;

    protected $trace;

    protected $mode;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->redis = Redis::Connection();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }
    }

    abstract public function getGatewayData($gatewayInput, $merchant, $merchantDetail);

    abstract public function processTerminalData($terminaldata, $merchant, $gatewayInput);

    abstract public function validateGatewayInput($gatewayInput, $merchantDetail);

    abstract public function checkDbConstraints($input, $merchant);

    abstract public function getLockResource($merchant, $gateway, $gatewayInput);

    abstract public function addDefaultValueToMerchantDetailIfApplicable(array &$merchantDetail);

    abstract public function getGatewayActionName();

    public function getGatewayName()
    {
        return $this->gateway;
    }
}
