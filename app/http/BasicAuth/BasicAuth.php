<?php

namespace Http\BasicAuth;

use Models\Key;
use Models\Merchant;
use Request;
use Response;
use EE;

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

    private $app = null;

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
            return Response::httpAuthExpected();
        }

        list($id, $pwd) = $this->getCredentials($request);

        if ($this->verifySecret($id, $pwd) === true)
            return;

        //
        // @todo: add check for internal IP here
        //
        if ($this->verifyApp($id, $pwd) === true)
            return;

        $error = new EE\Error\Error(
            EE\Error\ErrorCode::BAD_REQUEST_URL_NOT_FOUND);

        $error = $error->getPublicError();

        $httpStatusCode = $error->getHttpStatusCode();

        return Response::json($error->toArray(), $httpStatusCode);
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicAuth($route, $request)
    {
        if ($this->areCredentialsSet($request) === false)
        {
            return Response::httpAuthExpected();
        }

        list($id, $pwd) = $this->getCredentials($request);

        if ($this->verifyPublic($id) === false)
        {
            if ($this->verifySecret($id, $pwd) === false)
            {
                $error = new EE\Error\Error(
                    EE\Error\ErrorCode::BAD_REQUEST_URL_NOT_FOUND);

                $error = $error->getPublicError();

                $httpStatusCode = $error->getHttpStatusCode();

                return Response::json($error->toArray(), $httpStatusCode);
            }
        }

    }

    public function appAuth($route, $request)
    {
        if ($this->areCredentialsSet($request) === false)
        {
            return Response::httpAuthExpected();
        }

        list($id, $pwd) = $this->getCredentials($request);

        if ($this->verifyApp($id, $pwd) === false)
        {
            $error = new EE\Error\Error(
                EE\Error\ErrorCode::BAD_REQUEST_URL_NOT_FOUND);

            $error = $error->getPublicError();

            $httpStatusCode = $error->getHttpStatusCode();

            return Response::json($error->toArray(), $httpStatusCode);
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
    public function verifySecret($keyId, $keySecret)
    {
        $key = $this->fetchKey($keyId);

        if ($key === null)
        {
            return false;
        }

        $check = $this->matchSecret($keySecret, $key);

        if ($check === false)
        {
            return false;
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
    public function verifyPublic($keyId)
    {
        //
        // If the key is fetched successfully, then
        // public authentication is essentially successfully.
        //
        $key = $this->fetchKey($keyId);

        if ($key === null)
        {
            return false;
        }

        $this->fetchMerchantOfKey($key);

        return true;
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
    public function verifyApp($merchantId, $secret)
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
        if ($this->merchant === null)
            return;

        $id = $this->merchant->getKey();

        return (int) $id;
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
        return \Hash::check($keySecret, $key->getSecret());
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

    public static function unauthorized()
    {
        $error = new EE\Error\Error(EE\Error\ErrorCode::BAD_REQUEST_URL_NOT_FOUND);

        $error = $error->getPublicError();

        $httpStatusCode = $error->getHttpStatusCode();
    }
}