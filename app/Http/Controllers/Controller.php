<?php

namespace RZP\Http\Controllers;

use RZP\Constants\Entity as E;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App;
use Request;

use RZP\Constants\Entity as E;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $app;
    protected $trace;
    protected $repo;
    protected $route;

    /**
     * Service class name which this controller usage
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
     *
     * @param string|null $service
     *
     * @return \RZP\Models\Base\Service
     */
    protected function service($service = null)
    {
        if ($service !== null)
        {
            $ns = E::getEntityNamespace($service);
            $class = $ns . '\\' . 'Service';
            return new $class;
        }

        $class = $this->service;

        return new $class;
    }
}
