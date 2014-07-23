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

    public function checkHttps($request)
    {
        if (($request->getHttpHost() === 'api.razorpay.com') and
            ($request->secure() === false))
        {
            return ApiResponse::generateResponse(ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED);
        }
    }

    protected function areCredentialsSet($request)
    {
        list($id, $pwd) = $this->getCredentials($request);

        if (($id === null) or
            ($pwd === null))
            return false;

        return true;
    }

    protected function getCredentials($request)
    {
        $id = $request->getUser();

        $pwd = $request->getPassword();

        return array($id, $pwd);
    }

    public function privateAuth($route, $request)
    {
        if ($this->areCredentialsSet($request) === false)
        {
            return ApiResponse::httpAuthExpected();
        }

        list($id, $pwd) = $this->getCredentials($request);

        $response = $this->verifySecret($id, $pwd);

        if ($response === true)
            return;

        //
        // @todo: add check for internal IP here
        //
        if ($this->verifyApp($id, $pwd) === true)
            return;

        return $response;
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicAuth($route, $request)
    {
        if ($this->areCredentialsSet($request) === false)
        {
            return ApiResponse::httpAuthExpected();
        }

        list($id, $pwd) = $this->getCredentials($request);

        // @todo: throw error on public auth if
        //        secret is also provided.
        return $this->verifyPublic($id);
    }

    public function appAuth($route, $request)
    {
        if ($this->areCredentialsSet($request) === false)
        {
            return ApiResponse::httpAuthExpected();
        }

        list($id, $pwd) = $this->getCredentials($request);

        if ($this->verifyApp($id, $pwd) === false)
        {
            ApiResponse::routeNotFound();
        }
    }

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
    protected function verifySecret($keyId, $keySecret)
    {
        $key = $this->fetchKey($keyId);

        if ($key === null)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
        }

        if ($keySecret === '')
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $check = $this->matchSecret($keySecret, $key);

        if ($check === false)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }

        $this->fetchMerchantOfKey($key);

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
    protected function verifyPublic($keyId)
    {
        //
        // If the key is fetched successfully, then
        // public authentication is essentially successfully.
        //
        $key = $this->fetchKey($keyId);

        if ($key === null)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
        }

        $this->fetchMerchantOfKey($key);
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
     * Used for public authentication
     *
     * @param  string  $merchantId
     * @param  string  $secret
     * @return boolean
     */
    protected function verifyApp($merchantId, $secret)
    {
        $verify = $this->verifyAppSecret($secret);

        if ($verify === false)
        {
            return false;
        }

        $this->merchant = (new Merchant\Repository)->find($merchantId);

        return true;
    }

    public function getKey()
    {
        return $this->key;
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

    protected function matchSecret($keySecret, $key)
    {
        return Hash::check($keySecret, $key->getSecret());
    }

    protected function verifyAppSecret($secret)
    {
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

    protected function matchAppSecret($app, $secret)
    {
        return ($app['auth_pass'] === $secret);
    }
}