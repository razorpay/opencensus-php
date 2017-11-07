<?php

namespace RZP\Http;

use App;
use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\BasicAuth\Type;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\ThrottleException;
use RZP\Exception\BaseException;
use GrahamCampbell\Throttle\Facades\Throttle as ThrottleFacade;

class Throttle
{
    public function __construct($app)
    {
        $this->app = $app;

        $this->request = $this->app['request'];

        $this->router = $this->app['router'];

        $this->config = $this->app['config']->get('throttle');

        $this->trace = $this->app['trace'];
    }

    public function process($auth)
    {
        if ($this->config['skip'] === true)
        {
            return;
        }

        try
        {
            $this->throttle($auth);
        }
        catch (BaseException $e)
        {
            $this->trace->traceException($e);
        }
    }

    protected function throttle(string $auth)
    {
        $mode = $this->getMode($auth);

        $limits = $this->config['limits'][$mode];

        $limit = $limits[$auth] ?? $limits['default'];

        // Who is making the request
        $identifier =  $this->getIdentifier($auth, $mode);

        if (empty($identifier) === false)
        {
            $throttleData = [
                'ip'    => $this->request->ip(),
                'route' => $identifier,
            ];

            $time = $this->config['time_interval'];

            $throttle = ThrottleFacade::get($throttleData, $limit, $time);

            if ($throttle->attempt() === false)
            {
                $traceData = [
                    'ip'    => $this->request->ip(),
                    'route' => $this->request->route()->getName(),
                    'key'   => $this->getKeyId(),
                    'auth'  => $auth,
                    'limit' => $limit,
                    'count' => $throttle->count(),
                    'time'  => $time,
                ];

                if ($this->isThrottleMocked() === true)
                {
                    $this->trace->warning(TraceCode::REQUEST_THROTTLED, $traceData);

                    return;
                }

                throw new ThrottleException($time * 60, $traceData);
            }
        }
    }

    protected function isThrottleMocked()
    {
        $route = $this->request->route()->getName();

        return (in_array($route, Route::$throttledRoutes, true) === false);
    }

    protected function getIdentifier(string $auth, string $mode)
    {
        $routeName = $this->request->route()->getName();

        $identifier = $mode;

        switch ($auth)
        {
            case Type::ADMIN_AUTH:
                $resource = $routeName . $this->request->header(BasicAuth::ADMIN_TOKEN_HEADER);
                break;

            /**
             * Primary rate-limiting where we rate-limit
             */
            case Type::PRIVATE_AUTH:
                $resource = $this->getKeyId();
                break;

            /**
             * Most direct auth IPs will be
             * hitting a single specific route, so
             * model for that
             */
            case Type::DIRECT_AUTH:
                $resource = $routeName;
                break;

            case Type::DEVICE_AUTH:
                $resource = $this->request->getPassword();
                break;

            /**
             * Proxy auth requests are shared
             * between all users of a single merchant
             * against one dashboard instance
             */
            case Type::PROXY_AUTH:
                $resource = $this->getKeyId();
                break;

            /**
             * Rate Limit public auth on every route
             * This means that a user can make X
             * requests on a single specific public route
             * against all merchants in the time interval
             *
             * The highest we have seen any route being hit is
             * checkout_public
             */
            case Type::PUBLIC_AUTH:
                $resource = $this->request->route()->getName();
                break;
        }

        $identifier .= $resource;

        return $identifier;
    }

    protected function getKeyId()
    {
        $key = $this->request->getUser();

        return $key ? substr($key, 9) : '';
    }

    protected function getMode($auth)
    {
        $key = null;

        // Merchant key id is passed via multiple methods
        // in case of public auth
        if ($auth === 'public')
        {
            $key = $this->request->input('key_id');

            if ($key === null)
            {
                $key = $this->router->current()->parameter('key');
            }
        }

        $key = $key ?: $this->request->getUser();

        $mode = substr($key, 4, 4);

        if (Mode::exists($mode) === false)
        {
            $mode = Mode::LIVE;
        }

        return $mode;
    }
}
