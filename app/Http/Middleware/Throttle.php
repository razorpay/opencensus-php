<?php

namespace RZP\Http\Middleware;

use App;
use ApiResponse;

use Closure;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Http;
use Carbon\Carbon;
use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Http\BasicAuth\Type;
use Illuminate\Routing\Router;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\ThrottleException;
use Illuminate\Foundation\Application;
use GrahamCampbell\Throttle\Facades\Throttle as ThrottleFacade;

class Throttle
{
    /**
     * Application instance
     *
     * @var Application
     */
    protected $app;

    /**
     * @var BasicAuth
     */
    protected $ba;

    /**
     * @var Router
     */
    protected $router;

    /**
     * Throttle configs
     *
     * @var array
     */
    protected $throttleConfig;

    /**
     * Internal Apps configs
     *
     * @var array
     */
    protected $applicationsConfig;

    protected $request;

    /**
     * @var Logger
     */
    protected $trace;

    const STATIC_PRIVATE_IP = '1.1.1.1';

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->router = $app['router'];

        $this->request = $app['request'];

        $this->throttleConfig = $app['config']->get('throttle');

        $this->applicationsConfig = $app['config']->get('applications');

        $this->trace = $app['trace'];
    }

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request  $request
     * @param Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->throttleConfig['skip'] === false)
        {
            $route = $this->router->currentRouteName();

            $ret = $this->throttle($route);

            if ($ret !== null)
            {
                return $ret;
            }
        }

        return $next($request);
    }

    /**
     * Throttles as applicable
     *
     * @param string $route
     *
     * @return mixed
     */
    protected function throttle(string $route)
    {
        $ret = null;

        if (in_array($route, Http\Route::$internal, true) === true)
        {
            $this->process(Type::PRIVILEGE_AUTH);
        }
        else if (in_array($route, Http\Route::$admin, true) === true)
        {
            $this->process(Type::ADMIN_AUTH);
        }
        else if (in_array($route, Http\Route::$private, true) === true)
        {
            if ($this->isDashboard() === true)
            {
                $this->process(Type::PROXY_AUTH);
            }
            else
            {
                $this->process(Type::PRIVATE_AUTH);
            }
        }
        else if (in_array($route, Http\Route::$public, true) === true)
        {
            $this->process(Type::PUBLIC_AUTH);
        }
        else if (in_array($route, Http\Route::$publicCallback, true) === true)
        {
            $this->process(Type::PUBLIC_AUTH);
        }
        else if (in_array($route, Http\Route::$proxy, true) === true)
        {
            $this->process(Type::PROXY_AUTH);
        }
        else if (in_array($route, Http\Route::$device, true) === true)
        {
            $this->process(Type::DEVICE_AUTH);
        }
        else if (in_array($route, Http\Route::$direct, true) === true)
        {
            $this->process(Type::DIRECT_AUTH);
        }
        else
        {
            $ret = ApiResponse::routeNotFound();
        }

        return $ret;
    }

    protected function process($auth)
    {
        try
        {
            $mode = $this->getMode($auth);

            $limits = $this->throttleConfig['limits'][$mode];

            $limit = $limits[$auth] ?? $limits['default'];

            $throttleData = $this->getThrottleData($auth, $mode);

            if (empty($throttleData) === true)
            {
                return;
            }

            $time = $this->throttleConfig['time_interval'];

            $throttle = ThrottleFacade::get($throttleData, $limit, $time);

            if ($throttle->attempt() === false)
            {
                $traceData = [
                    'ip'    => $this->request->ip(),
                    'route' => $this->request->route()->getName(),
                    'key'   => $this->getKeyId($auth),
                    'auth'  => $auth,
                    'limit' => $limit,
                    'count' => $throttle->count(),
                    'time'  => $time,
                    'mode'  => $mode,
                ];

                if ($this->isThrottleMocked($auth) === true)
                {
                    $this->trace->warning(TraceCode::REQUEST_THROTTLED, $traceData);

                    return;
                }

                throw new ThrottleException($time * 60, $traceData);
            }
        }
        catch (ThrottleException $e)
        {
            throw $e;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
        }
    }

    protected function getMode($auth)
    {
        $key = $this->getKey($auth);

        // rzp_{live/test}_{key}
        $mode = substr($key, 4, 4);

        if (Mode::exists($mode) === false)
        {
            $mode = Mode::LIVE;
        }

        return $mode;
    }

    protected function getKey($auth)
    {
        $key = null;

        //
        // Merchant key id is passed via multiple
        // methods in case of public auth
        //
        if ($auth === Type::PUBLIC_AUTH)
        {
            $key = $this->request->input('key_id');

            if ($key === null)
            {
                //
                // This comes up in public callback auth routes
                // Public callback auth routes have auth set as
                // public auth for throttling purposes
                //
                $key = $this->router->current()->parameter('key');
            }
        }

        $key = $key ?: $this->request->getUser();

        return $key;
    }

    protected function getKeyId($auth)
    {
        $key = $this->getKey($auth);

        // rzp_{live/test}_{key}
        return $key ? substr($key, 9) : '';
    }

    protected function getThrottleData($auth, $mode)
    {
        $identifier = $this->getIdentifier($auth, $mode);
        $ip = $this->getIp($auth);

        if ((empty($identifier) === true) or
            (empty($ip) === true))
        {
            return [];
        }

        $throttleData = [
            'ip'    => $ip,
            'route' => $identifier,
        ];

        return $throttleData;
    }

    protected function getIdentifier(string $auth, string $mode)
    {
        $routeName = $this->request->route()->getName();

        $minute = Carbon::now(Timezone::IST)->minute;

        $identifier = $mode . $routeName . ':' . $minute;

        switch ($auth)
        {
            //
            // On Privilege Auth, same route can be accessed via different apps
            // Each app has a different password, we can use password for
            // differentiating the requests from different apps
            //
            case Type::PRIVILEGE_AUTH:
                $resource = $this->request->getPassword();
                break;

            case Type::ADMIN_AUTH:
                $resource = $this->request->header(Http\RequestHeader::X_DASHBOARD_ADMIN_EMAIL);
                break;

            //
            // Primary rate-limiting where we rate-limit
            //
            case Type::PRIVATE_AUTH:
                $resource = $this->getKeyId($auth);
                break;

            //
            // Most direct auth IPs will be
            // hitting a single specific route, so
            // model for that
            //
            case Type::DIRECT_AUTH:
                $resource = '';
                break;

            case Type::DEVICE_AUTH:
                $resource = $this->request->getPassword();
                break;

            //
            // Proxy auth requests are shared
            // between all users of a single merchant
            // against one dashboard instance
            //
            case Type::PROXY_AUTH:
                $resource = $this->request->header(Http\RequestHeader::X_DASHBOARD_USER_ID);
                break;

            //
            // Rate Limit public auth on every route
            // This means that a user can make X
            // requests on a single specific public route
            // against all merchants in the time interval
            //
            // The highest we have seen any route being hit is
            // checkout_public
            //
            case Type::PUBLIC_AUTH:
                $resource = $this->getKeyId($auth);
                break;

            default:
                throw new LogicException(
                    "Invalid auth passed for rate limiting",
                    ErrorCode::SERVER_ERROR_INVALID_AUTH,
                    [
                        'auth' => $auth,
                        'mode' => $mode,
                        'route_name' => $routeName
                    ]);
        }

        $identifier .= $resource;

        return $identifier;
    }

    protected function getIp($auth)
    {
        $ip = $this->request->ip();

        if ($auth === Type::PRIVATE_AUTH)
        {
            //
            // IP must have some value. It cannot be empty.
            // For private auth, we don't throttle on IP.
            // The merchant key itself is an identifier.
            // In case of public auth, the identifier is IP.
            //
            $throttleData['ip'] = self::STATIC_PRIVATE_IP;
        }

        return $ip;
    }

    protected function isDashboard()
    {
        $secret = $this->request->getPassword();

        return ($this->applicationsConfig['dashboard']['secret'] === $secret);
    }

    protected function isThrottleMocked($auth)
    {
        $route = $this->request->route()->getName();

        $nykaaThrottleRoutes = [
            'customer_create',
            'customer_fetch_tokens'
        ];

        $nestawayThrottleRoutes = [
            'payment_fetch_multiple'
        ];

        // Nykaa key id
        if (($this->getKeyId($auth) === 'zyRUD5exRM0CGk') and
            (in_array($route, $nykaaThrottleRoutes, true) === true))
        {
            return false;
        }

        // Nestaway key id
        if (($this->getKeyId($auth) === 'qaD5HXqij3FnDj') and
            (in_array($route, $nestawayThrottleRoutes, true) === true))
        {
            return false;
        }

        return (in_array($route, Route::$throttledRoutes, true) === false);
    }
}
