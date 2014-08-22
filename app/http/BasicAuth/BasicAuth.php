<?php

namespace Http\BasicAuth;

use Hash;
use Http\ApiResponse;
use Models\Key;
use Models\Merchant;
use EE\Error\ErrorCode;

class BasicAuth
{
    /*
     * Basic Auth currently goes as follows:
     * Public -
     * rzp_mode_keyId:
     *
     * Private -
     * rzp_mode_keyId:secret
     *
     * Application -
     * rzp_mode:secret
     *
     * Applicatoin proxy -
     * rzp_mode_merchantId:secret
     *
     */

    /**
     * Key id and secret sent by client for
     * basic auth
     * @var array
     */
    private $creds = array(
        'key' => '',
        'secret' => '');

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
    private $internalApp = null;

    /**
     * Authentication mode - test, live
     * @var string
     */
    private $mode;

    /**
     * Authentication type - private, public, internal
     * @var string
     */
    private $type;

    /**
     * Whether an internal app is doing an authentication
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

        if (($key === null) and
            ($secret === null))
            return ApiResponse::httpAuthExpected();

        $this->creds['secret'] = $secret;

        return $this->checkAndSetKeyId($key);
    }

    public function checkAndSetKeyId($key)
    {
        if (($this->validateKeyLength($key) === false) or
            ($this->validateKeyPrefix($key) === false) or
            ($this->validateAndSetMode($key) === false))
        {
            return $this->invalidApiKey();
        }

        $keyId = substr($key, 9);

        if ($keyId === false)
        {
            return;
        }

        $this->creds['key'] = $keyId;
    }

    protected function validateKeyLength($key)
    {
        $keyLen = strlen($key);

        return (($keyLen === 3 + 1 + 4 + 1 + 24) or
                ($keyLen === 3 + 1 + 4));
    }

    protected function validateKeyPrefix($key)
    {
        return (substr($key, 0, 4) === 'rzp_');
    }

    protected function validateAndSetMode($key)
    {
        $mode = substr($key, 4, 4);

        if ($mode === 'live')
        {
            $this->setMode(Mode::LIVE);
        }
        else if ($mode === 'test')
        {
            $this->setMode(Mode::TEST);
        }
        else
        {
            return false;
        }

        if ((strlen($key) > 8) and
            (substr($key, 8, 1) !== '_'))
        {
            return false;
        }

        return true;
    }

    protected function validateKeyExistence()
    {
        $keyId = $this->getKey();

        if ($keyId === '')
            return;

        //
        // For keys sent by merchants, make sure they exist in db.
        //
        $key = $this->fetchKey($keyId);

        return ($key !== null);
    }

    protected function invalidApiKey()
    {
       return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
    }

// --------------------- Basic Auths -------------------------------------------

    public function privateAuth()
    {
        if ($this->validateKeyExistence())
        {
            $response = $this->verifySecret();

            if ($response === true)
            {
                $this->setType(Type::PRIVATE_AUTH);

                return;
            }

            return $response;
        }
        else
        {
            //
            // @todo: add check for internal IP here
            //
            if ($this->verifyInternalAppAsProxy() === true)
                return;
        }

        return $this->invalidApiKey();
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicAuth()
    {
        if ($this->validateKeyExistence() === false)
        {
            return $this->invalidApiKey();
        }

        // @todo: throw error on public auth if
        //        secret is also provided.
        return $this->verifyPublic();
    }

    public function appAuth()
    {
        //
        // Check that key is blank
        //
        if ($this->verifyInternalApp() === true)
        {
            //
            // Check whether any internal app is
            // attempting authentication
            //
            if ($this->verifyInternalApp() === true)
            {
                return;
            }
        }

        // If we have a valid key, then send route not found
        // since we don't want to give away internal routes.
        // Otherwise say key not valid
        if ($this->validateKeyExistence() === false)
        {
            return $this->invalidApiKey();
        }
        else
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
        $keyEntity = $this->key;

        $secret = $this->getSecret();

        if ($secret === '')
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        if ($this->matchSecret($keyEntity) === false)
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
        $this->fetchMerchantOfKey($this->key);

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
    protected function verifyInternalAppAsProxy()
    {
        $verify = $this->verifyInternalAppSecret();

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

        return true;
    }

    protected function verifyInternalApp()
    {
        // Check key is blank
        if ($this->getKey() !== '')
        {
            return false;
        }

        if ($this->verifyClientIpInternal() === false)
        {
            return false;
        }

        if ($this->verifyInternalAppSecret() === false)
        {
            return false;
        }

        return true;
    }

    protected function verifyClientIpInternal()
    {
        // Check request is from internal ip
        $clientIp = $this->request->getClientIp();

        $clientIpRegex = '/^10\.0\.[0-9]{1,3}\.[0-9]{1,3}$/';

        if (preg_match($clientIpRegex, $clientIp) === false)
        {
            return false;
        }
    }

    protected function verifyInternalAppSecret()
    {
        $secret = $this->getSecret();

        $internalApps = \Config::get('applications');

        $verify = false;

        foreach ($internalApps as $name => $info)
        {
            $match = $this->matchInternalAppSecret($info, $secret);

            if ($match)
            {
                $verify = true;

                $this->internalApp = $name;

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

    protected function matchInternalAppSecret($internalAppInfo, $secret)
    {
        return ($internalAppInfo['secret'] === $secret);
    }
}