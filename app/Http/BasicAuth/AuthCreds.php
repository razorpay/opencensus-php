<?php

namespace RZP\Http\BasicAuth;

use Crypt;
use ApiResponse;

use RZP\Exception;
use RZP\Models\Key;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;

use Razorpay\OAuth\Client as OAuthClient;

class AuthCreds
{
    const API_KEY      = 'api_key';
    const CLIENT_ID    = 'client_id';

    const KEY          = 'key';
    const KEY_ID       = 'key_id';
    const ACCOUNT_ID   = 'account_id';
    const SECRET       = 'secret';
    const PUBLIC_KEY   = 'public_key';
    const PARTNER_TOKEN = 'partner_token';

    protected $type = self::API_KEY;


    protected $keyId = '';

    /**
     * Key used for authentication
     * @var Key\Entity
     */
    private $key = null;

    /**
     * Trace instance used for tracing
     * @var \Razorpay\Trace\Logger
     */
    protected $trace;

    /**
     * Used instead of api key for partner authentication
     * @var OAuthClient\Entity
     */
    private $partnerClient = null;

    /**
     * Used to identify partner flows
     * @var bool
     */
    private $isPartnerAuth = false;

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Authentication mode - test, live
     * @var string
     */
    private $mode;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Merchant who is being authenticated
     * either by himself or by an internal
     * application
     *
     * @var Merchant\Entity
     */
    private $merchant = null;

    /**
     * Key and secret sent by client for
     * basic auth.
     *
     * account_id   -> value passed in the ACCOUNT_HEADER_KEY, for account auth
     *
     * @var array
     */
    public $creds = [
        self::KEY           => '',
        self::KEY_ID        => '',
        self::PUBLIC_KEY    => '',
        self::SECRET        => '',
        self::ACCOUNT_ID    => '',
        self::PARTNER_TOKEN => '',
    ];

    /**
     * Contains valid lengths of key.
     * rzp_mode            = 3 + 1 + 4
     * rzp_mode_admin      = 3 + 1 + 4 + 1 + 5
     * rzp_mode_keyId      = 3 + 1 + 4 + 1 + 24
     * rzp_mode_merchantId = 3 + 1 + 4 + 1 + 14
     *
     * NOTE: key length 29 is used for OAuth public tokens,
     * hence DO NOT add 29 as a valid length for basicAuth
     *
     * @var array
     */
    public static $validKeyLengths = [
        8, 14, 23, 31, 33
    ];

    /**
     * Client types are interpreted differently in API vs
     * auth-service. We store the mapping here. API uses
     * test and live and restricts them the test/live modes
     * respectively. Auth-service refers to these as dev and
     * prod and the interpretation for Pure-platforms there
     * is not related to these modes from API.
     */
    protected static $clientModes = [
        'test' => 'dev',
        'live' => 'prod',
    ];

    protected static $validTypes = [
        self::API_KEY,
        self::CLIENT_ID,
    ];

    public function __construct($app, string $type = self::API_KEY, string $key)
    {
        $this->app = $app;

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        if (in_array($type, self::$validTypes) === false) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_KEY_TYPE,
                null,
                ['attributes' => $type]);
        }

        $this->type = $type;

        $this->key = $key;

        if ($this->type === self::CLIENT_ID) {
            $this->isPartnerAuth = true;
        }
    }

    public function isKeyExisting()
    {
        $keyId = $this->getKey();

        if ($keyId === '')
        {
            return false;
        }

        //
        // For keys sent by merchants, make sure they exist in db.
        // In case of partner, this will return partnerClient which
        // has partner id + secret that serve as credentials
        //
        if ($this->isPartnerAuth === true)
        {
            try
            {
                $this->partnerClient = (new OAuthClient\Repository)->getClientByIdAndEnv(
                    $keyId,
                    self::$clientModes[$this->getMode()]
                );
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::BAD_REQUEST_INVALID_CLIENT_KEY, [self::CLIENT_ID => $this->getKey()]);
            }

        }
        else
        {
            $this->fetchKey($keyId);
        }

        return ((empty($this->partnerClient) and empty($this->key)) === false);
    }

    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Verify key exists by fetching it
     * @return boolean
     */
    public function verifyKeyExistenceAndNotExpired()
    {
        if ($this->isKeyExisting() === false)
        {
            return $this->invalidApiKey();
        }

        return $this->verifyKeyNotExpired();
    }

    public function validateAndSetKeyId(string $key)
    {
        if (($this->verifyKeyLength($key) === false) or
            ($this->verifyKeyPrefix($key) === false) or
            ($this->verifyAndSetMode($key) === false))
        {
            return $this->invalidApiKey();
        }

        // In case of partner, the key will be something like rzp_test_partner_A0jg73G43ihI90
        // So in case of app auth this will return '' as expected and in other auths where id
        // is expected, it will be key_id or partner's client_id
        $keyId = substr(substr($key, 9), -14);

        $keyId = $keyId ?? '';

        $this->creds[self::KEY_ID] = $keyId;
    }

    protected function verifyKeyLength($key)
    {
        $keyLen = strlen($key);

        return in_array($keyLen, static::$validKeyLengths);
    }

    protected function verifyKeyPrefix($key)
    {
        return (substr($key, 0, 4) === 'rzp_');
    }

    protected function verifyAndSetMode($key)
    {
        $mode = substr($key, 4, 4);

        if (Mode::exists($mode) === true)
        {
            $this->setMode($mode);
        }
        else
        {
            return false;
        }

        if ((strlen($key) > 8) and (substr($key, 8, 1) !== '_'))
        {
            return false;
        }

        \Database\DefaultConnection::set($mode);

        return true;
    }

    public function verifyKeyNotExpired()
    {
        if ($this->isPartnerAuth === true)
        {
            $valid = (empty($this->partnerClient) === false);
        }
        else
        {
            $valid = ((empty($this->key) === false) and ($this->key->isExpired() === false));
        }

        if ($valid !== true)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED);
        }

        return true;
    }

    /**
     * Used for private/secret authentication.
     * These requests are expected to originate
     * from merchant's server
     *
     * @return bool|ApiResponse
     */
    public function verifySecret()
    {
        $secret = $this->getSecret();

        if ($secret === '')
        {
            $this->trace->info(TraceCode::BAD_REQUEST_API_SECRET_NOT_PROVIDED, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        if ($this->isPartnerAuth === true)
        {
            $client = $this->partnerClient;

            if ($client->getSecret() !== $secret)
            {
                $this->trace->info(
                    TraceCode::BAD_REQUEST_INVALID_API_SECRET, ['client_id' => $client->getSecret()]);

                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
            }
        }
        else
        {
            $keyEntity = $this->key;

            if (Crypt::decrypt($keyEntity->getSecret()) !== $secret)
            {
                $this->trace->info(
                    TraceCode::BAD_REQUEST_INVALID_API_SECRET, [self::KEY_ID => $this->getKey()]);

                return ApiResponse::unauthorized(
                    ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
            }
        }

        $this->fetchAndSetMerchantAndCheckLive();

        return true;
    }

    public function fetchAndSetMerchantAndCheckLive()
    {
        $credKey = $this->key;

        if ($this->isPartnerAuth === true)
        {
            $credKey = $this->partnerClient;
        }

        $merchantId = $credKey->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        // Here we do not need to check for activated in case of partner,
        //that check will be on the sub-merchant passed in the header
        if ($this->isPartnerAuth === false)
        {
            $this->setAndCheckMerchantActivatedForLive($merchant);
        }
        else
        {
            $this->setMerchant($merchant);
        }

        return $this->merchant;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }

    public function setModeAndDbConnection(string $mode)
    {
        $this->setMode($mode);

        \Database\DefaultConnection::set($mode);
    }

    public function setAndCheckMerchantActivatedForLive($merchant)
    {
        $this->setMerchant($merchant);

        $this->checkMerchantActivatedForLive();
    }

    /**
     * Sets $merchant instance var value by given $merchantId.
     * Called by OAuth flow. OAuth server response contains the same($merchantId).
     *
     * @param string $merchantId
     */
    public function setMerchantById(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->setMerchant($merchant);
    }

    public function setKeyEntity(Key\Entity $key = null)
    {
        $this->key = $key;
    }

    public function setMerchant($merchant)
    {
        if ($merchant !== null)
        {
            $this->setOrgId($merchant->org->getPublicId());
        }

        $this->merchant = $merchant;
    }

    public function checkMerchantActivatedForLive()
    {
        $mode = $this->getMode();

        if ($mode === Mode::TEST)
        {
            return;
        }

        if ($this->merchant->isActivated() === false)
        {
            throw new Exception\LogicException(
                'Must not be able to make live request when not activated');
        }
    }

    public function setOrgId($orgId)
    {
        $this->orgId = $orgId;
    }

    public function getSecret()
    {
        return $this->creds[self::SECRET];
    }

    protected function isPartnerAuth()
    {
        return ($this->isPartnerAuth === true);
    }

    public function getKey()
    {
        return $this->creds[self::KEY_ID];
    }

    public function getKeyEntity()
    {
        return $this->key;
    }

    public function setPublicKey(string $publicKey)
    {
        $this->creds[self::PUBLIC_KEY] = $publicKey;
    }

    protected function fetchKey($keyId)
    {
        $this->key = $this->repo->key->find($keyId);

        return $this->key;
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;

        $this->app['rzp.mode'] = $mode;
    }

    public function invalidApiKey()
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_API_KEY, [self::KEY_ID => $this->getKey()]);

        return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
    }
}
