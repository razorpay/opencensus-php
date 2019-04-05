<?php

namespace RZP\Http\Controllers\P2p;

use RZP\Http\Controllers;
use RZP\Models\P2p\Base\Action;
use RZP\Models\P2p\Base\Service;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as HttpRequest;

class Controller extends Controllers\Controller
{
    /**
     * @var Service
     */
    protected $service;

    /**
     * @var Action
     */
    protected $action;

    public function __construct()
    {
        parent::__construct();

        $this->service = $this->getServiceObject();

        $this->action  = $this->getActionObject();
    }

    protected function request(): HttpRequest
    {
        return Request::getFacadeRoot();
    }

    protected function response(array $response)
    {
        $response = $this->checkForNextAction($response);

        return response($response);
    }

    // TODO: Logic will change after entity naming convention
    protected function getServiceObject(): Service
    {
        $controllerClass = preg_replace('/^(.)*Controllers\\\/', '', static::class);

        $serviceName = str_replace('Controller', '\Service', $controllerClass);

        $serviceClass = \RZP\Models::class . '\\' . $serviceName;

        return new $serviceClass;
    }

    protected function getActionObject(): Action
    {
        $controllerClass = preg_replace('/^(.)*Controllers\\\/', '', static::class);

        $actionName = str_replace('Controller', '\Action', $controllerClass);

        $actionClass = \RZP\Models::class . '\\' . $actionName;

        return new $actionClass;
    }

    protected function checkForNextAction($response)
    {
        if ((isset($response['request']) === true) and
            (isset($response['callback']['action']) === true))
        {
            $route = $this->action->toRoute($response['callback']['action']);

            // First remove their is any data set in callback
            $data = $response['callback']['input']['data'] ?? [];
            unset($response['callback']['input']['data']);

            // Now merge gateway specific callback data in this
            $data['callback']= $response['callback']['gateway'];

            $query = http_build_query($data);

            $response['callback'] = route($route, $response['callback']['input']) . '?' . $query;
        }

        return $response;
    }
}
