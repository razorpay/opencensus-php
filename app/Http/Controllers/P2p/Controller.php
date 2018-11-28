<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Http\Controllers;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as HttpRequest;
use RZP\Models\P2p\Base\Service;

class Controller extends Controllers\Controller
{
    protected $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = $this->getServiceObject();
    }

    protected function request(): HttpRequest
    {
        return Request::getFacadeRoot();
    }

    protected function response(array $response)
    {
        return response($response, 200, [
            'Content-Type'          => 'application/json',
            'X-Razorpay-Request-Id' => str_random(40),
        ]);
    }

    // TODO: Logic will change after entity naming convention
    protected function getServiceObject()
    {
        $controllerClass = preg_replace('/^(.)*Controllers\\\/', '', static::class);

        $serviceName = str_replace('Controller', '\Service', $controllerClass);

        $serviceClass = \RZP\Models::class . '\\' . $serviceName;

        return new $serviceClass;
    }
}
