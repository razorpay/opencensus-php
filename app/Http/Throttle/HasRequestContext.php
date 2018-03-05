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
 *
 * TODO: Reuse this trait in BasicAuth class!
 */
trait HasRequestContext
{
    /**
     * @var string
     */
    protected $route;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $bearerToken;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $auth;

    //
    // In one request some (and not all) of below identifiers are set. Further
    // in throttle core logic we construct throttle key using the one available.
    //

    /**
     * @var string
     */
    protected $keyWithoutPrefix;

    /**
     * @var string
     */
    protected $keyId;

    /**
     * @var string
     */
    protected $mid;

    /**
     * @var string
     */
    protected $oauthAppId;

    /**
     * @var string
     */
    protected $oauthPublicToken;

    /**
     * @var string
     */
    protected $internalAppName;

    /**
     * @var string
     */
    protected $adminEmail;

    /**
     * @var bool
     */
    protected $proxy = false;

    protected function initRequestContextVars(Request $request)
    {
        $this->request = $request;
        $this->route   = $request->route()->getName();

        $this->setAuthVars();
        $this->setAdditionalVars();
    }

    protected function setAuthVars()
    {
        // Key can come
        // - as part of authentication header(http basic username)
        // - as part of route parameters for callback URLS
        // - in request input as key_id for public routes
        $key = $this->request->getUser() ?:
                $this->request->route()->parameter('key') ?:
                $this->request->input('key_id');

        // Direct authentication and bearer token case
        if (empty($key) === true)
        {
            return;
        }

        $this->validateKeyLen($key);

        $this->key              = $key;
        $this->keyWithoutPrefix = substr($key, 9) ?: null;
        $this->mode             = substr($key, 4, 4) ?: null;
        $this->secret           = $this->request->getPassword();
    }

    protected function setAdditionalVars()
    {
        if ($this->setAdditionalVarsForPublicAuth() == true)
        {
            $this->auth = Type::PUBLIC_AUTH;
        }
        else if ($this->setAdditionalVarsForPrivateAuth() == true)
        {
            $this->auth = Type::PRIVATE_AUTH;
        }
        else if ($this->setAdditionalVarsForDirectAuth() == true)
        {
            $this->auth = Type::DIRECT_AUTH;
        }
        else if ($this->setAdditionalVarsForPrivilegeAuth() == true)
        {
            $this->auth = Type::PRIVILEGE_AUTH;
        }
        else if ($this->setAdditionalVarsForDeviceAuth() == true)
        {
            $this->auth = Type::DEVICE_AUTH;
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function setAdditionalVarsForPublicAuth()
    {
        $isPublicRoute         = in_array($this->route, Route::$public, true);
        $isPublicCallbackRoute = in_array($this->route, Route::$publicCallback, true);

        if (($isPublicRoute === true) and
            ($this->isKeyOAuthPublicToken() === true))
        {
            // Further excludes "oauth_" part
            $this->oauthPublicToken = substr($this->keyWithoutPrefix, 6);
            return true;
        }
        else if (($isPublicRoute === true) or
                 ($isPublicCallbackRoute === true))
        {
            $this->keyId = $this->keyWithoutPrefix;
            return true;
        }

        return false;
    }

    protected function setAdditionalVarsForPrivateAuth()
    {
        $isPrivateRoute = in_array($this->route, Route::$private, true);
        $isProxyRoute   = in_array($this->route, Route::$proxy, true);

        if (($isPrivateRoute === true) and
            (empty($token = $this->getBearerToken()) === false))
        {
            $parsed           = (new Parser)->parse($token);
            $this->oauthAppId = $parsed->getClaim('aud');
            $this->mid        = $parsed->getClaim('merchant_id');
            return true;
        }
        else if ((($isPrivateRoute === true) and ($this->isDashboard() === true)) or
                 ($isProxyRoute === true))
        {
            $this->mid  = $this->keyWithoutPrefix;
            $this->proxy = true;
            return true;
        }
        else if (($isPrivateRoute === true) and
                 ($this->isDashboard() === false))
        {
            $this->keyId = $this->keyWithoutPrefix;
            return true;
        }

        return false;
    }

    protected function setAdditionalVarsForDirectAuth()
    {
        return in_array($this->route, Route::$direct, true);
    }

    protected function setAdditionalVarsForPrivilegeAuth()
    {
        // TODO:
        // - Now rate limit will apply across internal(e.g. crons) and admin usage.
        // I doubt if we want that? If we don't want that, I will revert this to
        // old logic where I was setting it all as explicit PROXY_AUTH, ADMIN_AUTH
        // etc. (inconsistent with how BasicAuth does it).

        if (in_array($this->route, Route::$internal, true) === true)
        {
            $this->internalAppName = $this->getInternalAppName();
            return true;
        }
        else if (in_array($this->route, Route::$admin, true) === true)
        {
            $this->adminEmail = $this->request->headers->get(RequestHeader::X_DASHBOARD_ADMIN_EMAIL);
            return true;
        }

        return false;
    }

    protected function setAdditionalVarsForDeviceAuth()
    {
        if (in_array($this->route, Route::$device, true) === true)
        {
            $this->keyId = $this->keyWithoutPrefix;
            return true;
        }

        return false;
    }

    protected function getInternalAppName()
    {
        foreach ($this->applications as $name => $config)
        {
            if (($config['secret'] ?? '') === $this->secret)
            {
                return $name;
            }
        }

        return null;
    }

    protected function isDashboard(): bool
    {
        return ($this->getInternalAppName() === 'dashboard');
    }

    protected function isPublicAuth(): bool
    {
        return ($this->auth === Type::PUBLIC_AUTH);
    }

    protected function isDirectAuth(): bool
    {
        return ($this->auth === Type::DIRECT_AUTH);
    }

    protected function getBearerToken()
    {
        return $this->isRunningUnitTests ? $this->request->bearerToken() : $this->getBearerTokenForApache();
    }

    protected function getBearerTokenForApache()
    {
        $headers = getallheaders()['Authorization'] ?? null;

        return starts_with($headers, 'Bearer ') ? substr($headers, 7) : '';
    }

    protected function isKeyOAuthPublicToken(): bool
    {
        return ((strlen($this->key) === OAuth::PUBLIC_TOKEN_LENGTH) and
                (substr($this->key, 8, 7) === '_oauth_'));
    }

    protected function validateKeyLen(string $key)
    {
        $validKeyLengths = array_merge(BasicAuth::$validKeyLengths, [OAuth::PUBLIC_TOKEN_LENGTH]);

        if (in_array(strlen($key), $validKeyLengths, true) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
        }
    }
}
