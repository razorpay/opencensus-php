<?php

namespace RZP\Http\BasicAuth;

use ApiResponse;
use Razorpay\OAuth\Client as OAuthClient;

use RZP\Exception;
use RZP\Models\Key;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;


abstract class AuthCreds
{
    const KEY          = 'key';
    const KEY_ID       = 'key_id';
    const ACCOUNT_ID   = 'account_id';
    const SECRET       = 'secret';
    const PUBLIC_KEY   = 'public_key';
    const PARTNER_TOKEN = 'partner_token';

    protected $keyId = '';

    /**
     * Key used for authentication
     * @var Key\Entity
     */
    protected $key = null;

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
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Authentication mode - test, live
     * @var string
     */
    protected $mode;

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
    protected $merchant = null;

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
     * key length 31 is for partners that use their dummy
     * client credentials for BasicAuth. The is something
     * like rzp_test_partner_dummyClientId1
     *
     * @var array
     */
    public static $validKeyLengths = [
        8, 14, 23, 31, 33
    ];

    public function __construct($app, string $key = '')
    {
        $this->app = $app;

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->key = $key;
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

    abstract protected function isKeyExisting();

    abstract protected function verifyKeyNotExpired();

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

    public function getSecret()
    {
        return $this->creds[self::SECRET];
    }

    public function getKey()
    {
        return $this->creds[self::KEY_ID];
    }

    public function setPublicKey(string $publicKey)
    {
        $this->creds[self::PUBLIC_KEY] = $publicKey;
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
