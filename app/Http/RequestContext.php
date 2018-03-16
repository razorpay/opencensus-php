<?php

namespace RZP\Http;

use Lcobucci\JWT\Parser;
use Illuminate\Http\Request;

use RZP\Http\OAuth;
use RZP\Http\Route;
use RZP\Models\Key;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\BasicAuth\Type;
use RZP\Foundation\Application;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\AuthCreds;
use RZP\Exception\BadRequestException;

/**
 * Extracts and holds various variables from request to be used in throttling and subsequent middle-wares.
 * This only does minimal database/redis calls, which is required even by throttle module.
 */
final class RequestContext
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
     * @var RepositoryManager
     */
    protected $repo;

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
    protected $mode;

    /**
     * @var string
     */
    protected $auth;

    /**
     * @var bool
     */
    protected $isRunningUnitTests;

    /**
     * @var array
     */
    protected $applications;

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
     * @var Key\Entity
     */
    protected $keyEntity;

    /**
     * @var string
     */
    protected $mid;

    /**
     * Every oauth application has dev & prod clients.
     * @var string
     */
    protected $oauthClientId;

    /**
     * @var string
     */
    protected $oauthPublicToken;

    /**
     * @var string
     */
    protected $bearerToken;

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

    public function __construct(Application $app)
    {
        $this->request            = $app['request'];
        $this->repo               = $app['repo'];

        $this->route              = $this->request->route()->getName();
        $this->isRunningUnitTests = $app->runningUnitTests();
        $this->applications       = $app['config']->get('applications');

        $this->setAuthVars();
        $this->setAdditionalVars();
        $this->resolveKeyIdIfApplicable();
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function getKeyWithoutPrefix()
    {
        return $this->keyWithoutPrefix;
    }

    public function getKeyId()
    {
        return $this->keyId;
    }

    public function getMid()
    {
        return $this->mid;
    }

    public function getOauthClientId()
    {
        return $this->oauthClientId;
    }

    public function getOauthPublicToken()
    {
        return $this->oauthPublicToken;
    }

    public function getBearerToken()
    {
        return $this->bearerToken;
    }

    public function getInternalAppName()
    {
        return $this->internalAppName;
    }

    public function getAdminEmail()
    {
        return $this->adminEmail;
    }

    public function getProxy(): bool
    {
        return $this->proxy;
    }

    public function isDashboard(): bool
    {
        return ($this->internalAppName === 'dashboard');
    }

    public function isPublicAuth(): bool
    {
        return ($this->auth === Type::PUBLIC_AUTH);
    }

    public function isDirectAuth(): bool
    {
        return ($this->auth === Type::DIRECT_AUTH);
    }

    public function getBearerTokenFromRequest()
    {
        return $this->isRunningUnitTests ? $this->request->bearerToken() : $this->getBearerTokenFromRequestForApache();
    }

    public function getBearerTokenFromRequestForApache()
    {
        $headers = getallheaders()['Authorization'] ?? null;

        return starts_with($headers, 'Bearer ') ? substr($headers, 7) : '';
    }

    public function isKeyOAuthPublicToken(): bool
    {
        return ((strlen($this->key) === OAuth::PUBLIC_TOKEN_LENGTH) and (substr($this->key, 8, 7) === '_oauth_'));
    }


    /**
     * Protected Methods
     */

    /**
     * Extracts user authentication information from request and sets corresponding instance variables.
     */
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
        $this->secret           = $this->request->getPassword();

        $mode       = substr($key, 4, 4) ?: null;
        $this->mode = Mode::exists($mode) ? $mode : null;
    }

    /**
     * Extracts additional information from requests per route's auth group.
     */
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

    /**
     * Resolves $keyId and sets $key entity instance as well as $mid instance.
     */
    protected function resolveKeyIdIfApplicable()
    {
        if ((empty($this->keyId) === true) or (empty($this->mode) === true) or (Mode::exists($this->mode) === false))
        {
            return;
        }

        $this->keyEntity = $this->repo->key->connection($this->mode)->findOrFailPublic($this->keyId);
        $this->mid       = $this->keyEntity->getMerchantId();
    }

    protected function setAdditionalVarsForPublicAuth()
    {
        $isPublicRoute         = in_array($this->route, Route::$public, true);
        $isPublicCallbackRoute = in_array($this->route, Route::$publicCallback, true);

        // Route belongs neither to public or public callback group
        if (($isPublicRoute === false) and ($isPublicCallbackRoute === false))
        {
            return false;
        }

        // Route belongs to one of 2 groups and accessed via oauth public token
        if ($this->isKeyOAuthPublicToken() === true)
        {
            // Further excludes "oauth_" part
            $this->oauthPublicToken = substr($this->keyWithoutPrefix, 6);
        }
        // Route belongs to one of 2 groups and accessed normally via key id
        else
        {
            $this->keyId = $this->keyWithoutPrefix;
        }

        return true;
    }

    protected function setAdditionalVarsForPrivateAuth()
    {
        $isPrivateRoute = in_array($this->route, Route::$private, true);
        $isProxyRoute   = in_array($this->route, Route::$proxy, true);

        if (($isPrivateRoute === true) and (empty($token = $this->getBearerTokenFromRequest()) === false))
        {
            $parsed              = (new Parser)->parse($token);
            $this->oauthClientId = $parsed->getClaim('aud');
            $this->mid           = $parsed->getClaim('merchant_id');
            return true;
        }
        else if ((($isPrivateRoute === true) and ($this->isDashboard() === true)) or ($isProxyRoute === true))
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
        if (in_array($this->route, Route::$internal, true) === true)
        {
            $this->setInternalAppName();
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

    protected function setInternalAppName()
    {
        foreach ($this->applications as $name => $config)
        {
            if (($config['secret'] ?? '') === $this->secret)
            {
                $this->internalAppName = $name;
                return;
            }
        }
    }

    protected function validateKeyLen(string $key)
    {
        $validKeyLengths = array_merge(AuthCreds::$validKeyLengths, [OAuth::PUBLIC_TOKEN_LENGTH]);

        if (in_array(strlen($key), $validKeyLengths, true) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
        }
    }
}
