<?php

namespace RZP\Http\Throttle;

use Lcobucci\JWT\Parser;
use Illuminate\Http\Request;

use RZP\Http\OAuth;
use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\BasicAuth\Type;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\BadRequestException;

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
    private $bearerToken;
    private $mode;
    private $auth;

    //
    // In one request some(and not all) of below identifiers are set. Further
    // in throttle core logic we construct throttle key using the one available.
    //

    private $keyId;
    private $mid;
    private $oauthAppId;
    private $oauthPublicToken;
    private $internalAppName;
    private $adminEmail;
    private $device;

    private function initRequestContextVars(Request $request)
    {
        $this->request = $request;
        $this->route   = $this->router->currentRouteName();

        $this->setAuthVars();
        $this->setAdditionalVars();
    }

    private function setAuthVars()
    {
        // Key can come
        // - in request input as key_id for public routes
        // - as part of route parameters for callback URLS
        // - as part of route parameters for callback URLS
        $key = $this->request->input('key_id') ?:
                $this->router->current()->parameter('key') ?:
                $this->request->getUser();

        // Direct authentication and bearer token case
        if (empty($key) === true)
        {
            return;
        }
        // Validate key length
        $validKeyLengths   = BasicAuth::$validKeyLengths;
        $validKeyLengths[] = OAuth::PUBLIC_TOKEN_LENGTH;
        if (in_array(strlen($key), $validKeyLengths) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
        }

        $this->key    = $key;
        $this->mode   = substr($key, 4, 4);
        $this->secret = $this->request->getPassword();
    }

    private function setAdditionalVars()
    {
        $key = substr($this->key, 9); // Excluding rzp_test_ prefix

        if (in_array($this->route, Route::$internal, true) === true)
        {
            $this->auth        = Type::PRIVILEGE_AUTH;
            $this->internalapp = $this->getInternalAppName($secret);
        }
        else if (in_array($this->route, Route::$admin, true) === true)
        {
            $this->auth       = Type::ADMIN_AUTH;
            $this->adminEmail = $request->headers(RequestHeader::X_DASHBOARD_ADMIN_EMAIL);
        }
        else if ((in_array($this->route, Route::$private, true) === true) and
            (empty($token = $this->getBearerToken()) === false))
        {
            $this->auth = Type::PRIVATE_AUTH;

            $parsed           = (new Parser)->parse($token);
            $this->oauthAppId = $parsed->getClaim('aud');
            $this->mid        = $parsed->getClaim('merchant_id');
        }
        else if ((in_array($this->route, Route::$private, true) === true) and
            ($this->isDashboard() === true))
        {
            $this->auth = Type::PROXY_AUTH;
            $this->mid  = $key;
        }
        else if ((in_array($this->route, Route::$private, true) === true) and
            ($this->isDashboard() === false))
        {
            $this->auth  = Type::PRIVATE_AUTH;
            $this->keyId = $key;
        }
        else if ((in_array($this->route, Route::$public, true) === true) and
            ($this->isKeyOAuthPublicToken() === true))
        {
            $this->auth             = Type::PUBLIC_AUTH;
            $this->oauthPublicToken = substr($key, 6); // Further excludes oauth_ part :)
        }
        else if (in_array($this->route, Route::$public, true) === true)
        {
            $this->auth  = Type::PUBLIC_AUTH;
            $this->keyId = $key;
        }
        else if (in_array($this->route, Route::$publicCallback, true) === true)
        {
            $this->auth  = Type::PUBLIC_AUTH;
            $this->keyId = $key;
        }
        else if (in_array($this->route, Route::$proxy, true) === true)
        {
            $this->auth = Type::PROXY_AUTH;
            $this->mid  = $key;
        }
        else if (in_array($this->route, Route::$device, true) === true)
        {
            $this->auth   = Type::DEVICE_AUTH;
            $this->device = $secret;
        }
        else if (in_array($this->route, Route::$direct, true) === true)
        {
            $this->auth = Type::DIRECT_AUTH;
        }
    }

    // TODO: Following methods are redundant between here and at least
    //       one more place in \RZP\Http namespace. Move these out.

    private function getInternalAppName()
    {
        foreach ($this->applications as $name => $config)
        {
            if (($config['secret'] ?? '') === $this->secret)
            {
                return $name;
            }
        }
    }

    private function isDashboard(): bool
    {
        return ($this->getInternalAppName() === 'dashboard');
    }

    private function isPublicAuth(): bool
    {
        return ($this->auth === Type::PUBLIC_AUTH);
    }

    private function getBearerToken(): string
    {
        return $this->isUnitTests ? $this->request->bearerToken() : $this->getBearerTokenForApache();
    }

    private function getBearerTokenForApache(): string
    {
        $headers = getallheaders()['Authorization'] ?? null;

        return starts_with($headers, 'Bearer ') ? substr($headers, 7) : '';
    }

    private function isKeyOAuthPublicToken(): bool
    {
        return ((strlen($this->key) === OAuth::PUBLIC_TOKEN_LENGTH) and
                (substr($this->key, 8, 7) === '_oauth_'));
    }
}
