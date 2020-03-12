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
        // Base processor makes sure that these two set for next action
        if ((isset($response['request']) === true) and
            (isset($response['callback']['action']) === true))
        {
            $route = $this->action->toRoute($response['callback']['action']);

            // The callback is generated from two parts
            // 1. which processor adds by itself, which is used by processor only
            // 2. which gateway passes by itself, which is only used by processor
            // Now, processor specific data goes on first level and gateway specific goes inside callback field

            // First remove if there is any data set in callback
            // This is the callback input which can not be part of url
            // Example post parameters like username, bank_account_id
            $data = $response['callback']['input']['data'] ?? [];
            unset($response['callback']['input']['data']);

            // Now add gateway specific callback data in this
            $data['callback']= $response['callback']['gateway'];

            // if we had to maintain the consistency, we can put a hash with query
            // Which the controller itself can verify
            $query = http_build_query($data);

            // Append question mark only when query is not empty
            $append = empty($query) ? '' : ('?' . $query);

            // The callback input is used to resolve the URL path itself
            // By default the route method set url path to be absolute
            $response['callback'] = route($route, $response['callback']['input']) . $append;
        }

        return $response;
    }
}
