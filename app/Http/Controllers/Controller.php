<?php

namespace RZP\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App;
use Request;

use RZP\Constants\Entity as E;
use RZP\Http\Middleware\SaveApiDetailsForDocumentation;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $app;
    protected $trace;
    protected $repo;
    protected $route;

    /**
     * Service class name which this controller uses
     *
     * @var string
     */
    protected $service;

    /**
     * HTTP request input
     *
     * @var array
     */
    protected $input;

    protected $config;

    protected $ba;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->config = $this->app['config'];

        $this->route = $this->app['api.route'];

        $this->ba = $this->app['basicauth'];

        $this->input = Request::all();
    }

    protected function getCheckoutCommon(array $input = [])
    {
        $context = $this->config->get('app.context');

        $url = $this->config->get('app.checkout');

        $urlMap = $this->config->get('url.checkout');

        $cdnUrlMap = $this->config->get('url.cdn');

        $framejs = '/v1/checkout-frame.js';

        $css = '/v1/css/checkout.css';

        $font = '/lato';

        $data = [];

        if (isset($input['canary']) === true && $context === 'production') {
            $context = 'canary';
        }

        if (in_array($context, array_keys($urlMap)))
        {
            $url = $urlMap[$context];
        }
        else if (isset($input['checkout']))
        {
            $url = $input['checkout'];
        }

        $data['checkout'] = $url;
        $data['framejs'] = $url . $framejs;
        $data['css'] = $url . $css;
        $data['font'] = $cdnUrlMap['production'].$font;

        return $data;
    }

    /**
     * Returns the service instance.
     * Three ways to get the service instance:
     *  1. If entity is passed to the function, we get the class
     *     from the entity namespace.
     *  2. If service variable is defined in the child controller class,
     *     we get an object of the class defined by the service variable.
     *  3. If both the above conditions don't match, we get the entity name
     *     using the child controller class name and follow the (1) flow.
     *
     * @param null $entity
     *
     * @return mixed Either Base\Service or external package services like OAuth
     */
    protected function service($entity = null)
    {
        if ($entity !== null)
        {
            $class = E::getEntityService($entity);
        }
        else if ($this->service !== null)
        {
            $class = $this->service;
        }
        else
        {
            $controllerClassFQN = explode('\\', static::class);

            $controllerClass = end($controllerClassFQN);

            $classNameArray = explode("Controller", $controllerClass);

            $entity = snake_case($classNameArray[0]);

            $class = E::getEntityService($entity);
        }

        return new $class;
    }
}
