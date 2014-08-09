<?php

namespace Http\BasicAuth;

use Hash;
use Http\ApiResponse;
use Models\Key;
use Models\Merchant;
use EE\Error\ErrorCode;

class BasicAuth
{
    /**
     * Key used for authentication
     * @var Key\Entity
     */
    private $key = null;

    /**
     * Merchant who is being authenticated
     * either by himself or by an internal
     * application
     *
     * @var Merchant\Entity
     */
    private $merchant = null;

    /**
     * During app authentication, the app
     * which has been authenticated.
     *
     * @var string
     */
    private $app = null;

    /**
     * Authentication mode - test, live
     * @var string
     */
    private $mode;

    /**
     * Authentication type - private, public, app
     * @var string
     */
    private $type;

    /**
     * Whether an app is doing an authentication
     * proxy to perform some action on merchant's
     * behalf
     * @var boolean
     */
    private $proxy;

    /**
     * Laravel request class instance
     * @var [type]
     */
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function checkHttps()
    {
        if (($this->request->getHttpHost() === 'api.razorpay.com') and
            ($this->request->secure() === false))
        {
            return ApiResponse::generateResponse(ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED);
        }
    }

    public function setCredentials()
    {
        $key = $this->request->getUser();

        $secret = $this->request->getPassword();

        if (($key === null) or
            ($secret === null))
            return ApiResponse::httpAuthExpected();

        $this->creds['key'] = $key;
        $this->creds['secret'] = $secret;

        return $this->checkAndSetMode();
    }

    public function checkAndSetMode()
    {
        $key = $this->getKey();

        // @todo: remove this after infra moves to key labels
        $this->setMode(Mode::TEST);

        if (($key !== '') and
            (strlen($key) < 24))
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);

        if (substr($key, 0, 4) !== 'rzp_')
            return;

        $mode = substr($key, 4, 5);

        $modeSupplied = true;

        if ($mode === 'live_')
        {
            $this->setMode(Mode::LIVE);
        }
        else if ($mode === 'test_')
        {
            $this->setMode(Mode::TEST);
        }
        else if ($mode === 'appn_')
        {
            $this->setMode(Mode::APPN);
        }
        else
        {
            $modeSupplied = false;
        }

        if ($modeSupplied)
        {
            $this->creds['key'] = substr($key, 9);
        }
    }

// --------------------- Basic Auths -------------------------------------------

    public function privateAuth()
    {
        $response = $this->verifySecret();

        if ($response === true)
        {
            $this->setType(Type::PRIVATE_AUTH);

            return;
        }

        //
        // @todo: add check for internal IP here
        //
        if ($this->verifyAppAsProxy() === true)
            return;

        return $response;
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicAuth()
    {
        // @todo: throw error on public auth if
        //        secret is also provided.
        return $this->verifyPublic();
    }

    public function appAuth()
    {
        if ($this->getKey() !== '')
        {
            return ApiResponse::routeNotFound();
        }

        if ($this->verifyApp() === false)
        {
            return ApiResponse::routeNotFound();
        }
    }

// --------------------- Basic Auths Ends --------------------------------------

// --------------------- Verifiers ---------------------------------------------

    /**
     * Checks if given key id is present
     * in database or not. Only non-expired keys
     * are checked. If there, then it's secret
     * is matched against the one provided.
     *
     * Used for private/secret authentication.
     * These requests are expected to originate
     * from merchant's server
     *
     * @param  string   $keyId
     * @param  string   $keySecret
     * @return boolean
     */
    protected function verifySecret()
    {
        $keyEntity = $this->fetchKey($this->getKey());

        $secret = $this->getSecret();

        if ($keyEntity === null)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
        }

        if ($secret === '')
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $check = $this->matchSecret($keyEntity);

        if ($check === false)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }

        $this->fetchMerchantOfKey($keyEntity);

        return true;
    }

    /**
     * Checks if given key id is present in
     * database or not. Only non-expired keys
     * are checked.
     *
     * Used for public authentication
     *
     * @param  string  $keyId
     * @return boolean
     */
    protected function verifyPublic()
    {
        //
        // If the key is fetched successfully, then
        // public authentication is essentially successfully.
        //
        $key = $this->fetchKey($this->getKey());

        if ($key === null)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
        }

        $this->fetchMerchantOfKey($key);

        $this->setType(Type::PUBLIC_AUTH);
    }

    /**
     * Matches the secret provided against the list of
     * applications secrets with us. If any matches, then
     * that  particular app is allowed to continue
     * it's operation.
     *
     * If a merchant id is provided, then the app is
     * authenticating as that merchant and trying to
     * perform operations related to that merchant.
     *
     * Used for private authentication fails
     *
     * @return boolean
     */
    protected function verifyAppAsProxy()
    {
        $verify = $this->verifyAppSecret();

        if ($verify === false)
        {
            return false;
        }

        $merchantId = $this->getKey();

        $this->merchant = (new Merchant\Repository)->find($merchantId);

        if ($this->merchant === null)
        {
            return false;
        }

        $this->setMode(Type::APP_AUTH);

        return true;
    }

    protected function verifyApp()
    {
        $secret = $this->getSecret();
        $verify = $this->verifyAppSecret($secret);

        if ($verify === false)
        {
            return false;
        }

        $this->setMode(Type::APP_AUTH);

        return true;
    }

    protected function verifyAppSecret()
    {
        $secret = $this->getSecret();

        $apps = \Config::get('applications');

        $verify = false;

        foreach ($apps as $name => $app)
        {
            $match = $this->matchAppSecret($app, $secret);

            if ($match)
            {
                $verify = true;

                $this->app = $name;

                break;
            }
        }

        return $verify;
    }

// --------------------- Verifiers Ends ----------------------------------------

// --------------------- Getters -----------------------------------------------

    protected function getKey()
    {
        return $this->creds['key'];
    }

    private function getSecret()
    {
        return $this->creds['secret'];
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }

    public function getMerchantId()
    {
        return $this->merchant->getKey();
    }

    public function getPublicKey()
    {
        return $this->key->getKey();
    }

// --------------------- Getters Ends ------------------------------------------

// --------------------- Setters -----------------------------------------------

    protected function setMode($mode)
    {
        $this->mode = $mode;
    }

    protected function setType($type)
    {
        $this->type = $type;
    }

// --------------------- Setters Ends ------------------------------------------

    protected function fetchKey($keyId)
    {
        $this->key = (new Key\Repository)->findNotExpired($keyId);

        return $this->key;
    }

    protected function fetchMerchantOfKey($key)
    {
        $merchantId = $key->getMerchantId();

        $this->merchant = (new Merchant\Repository)->findOrFail($merchantId);

        return $this->merchant;
    }

    protected function matchSecret($key)
    {
        $secret = $this->getSecret();

        return Hash::check($secret, $key->getSecret());
    }

    protected function matchAppSecret($app, $secret)
    {
        return ($app['secret'] === $secret);
    }
}