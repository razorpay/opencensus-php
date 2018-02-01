<?php

namespace RZP\Http\Throttle;

use Illuminate\Http\Request;

use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Http\RequestHeader;
use RZP\Http\BasicAuth\Type;

/**
 * Extracts various variables from request to be used
 * in throttling logic. This doesn't do any database/
 * redis calls etc.
 *
 * Keeping this outside of core throttle logic to maintain
 * clarity.
 */
trait HasRequestContext
{
    private $route;
    private $request;
    private $key;
    private $secret;
    private $mode;
    private $auth;

    //
    // In one request some(and not all) of below identifiers are set. Further
    // in throttle core logic we construct throttle key using the one available.
    //

    private $keyId;
    private $mid;
    private $oauthAppId;
    private $internalAppName;
    private $adminEmail;
    private $device;

    private function initRequestContextVars(Request $request)
    {
        $this->request = $request;
        $this->route   = $this->router->currentRouteName();

        //
        // Key can come
        // - in request input as key_id for public routes
        // - as part of route parameters for callback URLS
        // - as part of route parameters for callback URLS
        //
        $key = $this->request->input('key_id') ?:
                $this->router->current()->parameter('key') ?:
                $this->request->getUser();

        // Key is actually just the part after rzp_{$mode}_
        $this->key = substr($key, 9);
        $this->mode = substr($key, 4, 4);
        // Just for not getting broken elsewhere if someone sends incorrect key
        $this->mode = Mode::exists($this->mode) ? $this->mode : Mode::LIVE;

        $this->secret = $this->request->getPassword();

        if (in_array($this->route, Route::$internal, true) === true)
        {
            $this->auth = Type::PRIVILEGE_AUTH;
            $this->internalapp = $this->getInternalAppName($secret);
        }
        else if (in_array($this->route, Route::$admin, true) === true)
        {
            $this->auth = Type::ADMIN_AUTH;
            $this->adminEmail = $request->headers(RequestHeader::X_DASHBOARD_ADMIN_EMAIL);
        }
        else if ((in_array($this->route, Route::$private, true) === true) and
            ($this->isDashboard($request) === true))
        {
            $this->auth = Type::PROXY_AUTH;
            $this->mid = $this->key;
        }
        else if ((in_array($this->route, Route::$private, true) === true) and
            ($this->isDashboard($request) === false))
        {
            $this->auth = Type::PRIVATE_AUTH;
            $this->keyId = $this->key;
        }
        else if (in_array($this->route, Route::$public, true) === true)
        {
            $this->auth = Type::PUBLIC_AUTH;
            $this->keyId = $this->key;
        }
        else if (in_array($this->route, Route::$publicCallback, true) === true)
        {
            $this->auth = Type::PUBLIC_AUTH;
            $this->keyId = $this->key;
        }
        else if (in_array($this->route, Route::$proxy, true) === true)
        {
            $this->auth = Type::PROXY_AUTH;
            $this->mid = $this->key;
        }
        else if (in_array($this->route, Route::$device, true) === true)
        {
            $this->auth = Type::DEVICE_AUTH;
            $this->device = $secret;
        }
        else if (in_array($this->route, Route::$direct, true) === true)
        {
            $this->auth = Type::DIRECT_AUTH;
        }
    }

    private function getInternalAppName(string $secret)
    {
        foreach ($this->applications as $name => $config)
        {
            if (($config['secret'] ?? '') === $secret)
            {
                return $name;
            }
        }
    }

    private function isDashboard(string $secret): bool
    {
        return ($this->getInternalAppName($secret) === 'dashboard');
    }

    private function isPrivateAuth(): bool
    {
        return ($this->auth === Type::PRIVATE_AUTH);
    }
}
