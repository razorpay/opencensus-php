<?php

namespace RZP\Http;

use App;
use RZP\Constants\Mode;
use RZP\Http\BasicAuth\Type;
use RZP\Http\BasicAuth\BasicAuth;
use GrahamCampbell\Throttle\Facades\Throttle as ThrottleFacade;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class Throttle
{
    public function __construct($app)
    {
        $this->app = $app;

        $this->request = $this->app['request'];

        $this->router = $this->app['router'];

        $this->config = $this->app['config']->get('throttle');
    }

    public function process($auth)
    {
        if ($this->config['skip'] === true)
        {
            return;
        }

        $mode = $this->getMode($auth);

        $limits = $this->config['limits'][$mode];

        $limit = $limits[$auth] ?? $limits['default'];

        // Who is making the request
        $identifier =  $this->getIdentifier($auth);

        if (empty($identifier) === false)
        {
            $throttleData = [
                'ip'    => $this->request->ip(),
                'route' => $identifier,
            ];

            $time = $this->config['time_interval'];

            if (ThrottleFacade::get($throttleData, $limit, $time)->attempt() === false)
            {
                throw new TooManyRequestsHttpException(
                    $time * 60, 'Rate limit exceeded.');
            }
        }
    }

    protected function getIdentifier($auth)
    {
        switch ($auth)
        {
            case Type::ADMIN_AUTH:
                return $this->request->header(BasicAuth::ADMIN_TOKEN_HEADER);

            /**
             * Primary rate-limiting where we rate-limit
             */
            case Type::PRIVATE_AUTH:
                return $this->getKeyId();

            /**
             * Most direct auth IPs will be
             * hitting a single specific route, so
             * model for that
             */
            case Type::DIRECT_AUTH:
                return $this->request->route()->getName();

            case Type::DEVICE_AUTH:
                return $this->request->getPassword();

            /**
             * Proxy auth requests are shared
             * between all users of a single merchant
             * against one dashboard instance
             */
            case Type::PROXY_AUTH:
                return $this->getKeyId();

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
                return $this->request->route()->getName();
        }
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

        return $mode ?: Mode::LIVE;
    }
}
