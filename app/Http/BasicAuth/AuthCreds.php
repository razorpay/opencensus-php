<?php

namespace RZP\Http\BasicAuth;

use ApiResponse;
use Razorpay\Trace\Logger as Trace;
use Razorpay\OAuth\Client as OAuthClient;

use RZP\Constants\HyperTrace;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Key;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Key\Metric;
use RZP\Constants\Product;
use RZP\Http\RequestContext;
use RZP\Base\RepositoryManager;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\Tracer;


abstract class AuthCreds
{
    const KEY                     = 'key';
    const KEY_ID                  = 'key_id';
    const ACCOUNT_ID              = 'account_id';
    const SECRET                  = 'secret';
    const PUBLIC_KEY              = 'public_key';

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
     * @var RequestContext
     */
    protected $reqCtx;

    protected $razorx;

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
    ];

    /**
     * Contains valid lengths of key.
     * rzp_mode                                = 3 + 1 + 4
     * rzp_mode_admin                          = 3 + 1 + 4 + 1 + 5
     * rzp_mode_keyId                          = 3 + 1 + 4 + 1 + 24
     * rzp_mode_merchantId                     = 3 + 1 + 4 + 1 + 14
     * rzp_mode_partner_clientID               = 3 + 1 + 4 + 1 + 7 + 1 + 14
     * rzp_mode_partner_clientID-acc_accountId = 3 + 1 + 4 + 1 + 7 + 1 + 14 + 1 + 3 + 1 + 14
     *
     * NOTE: key length 29 is used for OAuth public tokens,
     * hence DO NOT add 29 as a valid length for basicAuth
     *
     * key length 31 is for partners that use their dummy
     * client credentials for BasicAuth. The is something
     * like rzp_test_partner_dummyClientId1
     * The partner auth callback key is of length 50 like
     * rzp_test_partner_dummyClientId1-acc_accountId
     *
     * @var array
     */
    public static $validKeyLengths = [
        8, 14, 23, 31, 33, 50
    ];

    public function __construct($app, string $key = '')
    {
        $this->app = $app;

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->key = $key;

        $this->reqCtx = $app['request.ctx'];

        $this->razorx = $app->razorx;

        // By default username, and public_key are same if not public_key gets updated in setPublicKey func further.
        $this->app['basicauth']->setPassportCredentialClaims($this->key, $this->key);
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

    abstract public function isKeyExisting();

    abstract public function verifyKeyNotExpired();

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
            $isPrivateXRouteAccessible = Tracer::inspan(['name' => HyperTrace::AUTH_CRED_CAN_NON_KYC_ACTIVATED_MERCHANT_ACCESS_PRIVATE_X_ROUTES], function ()
                {
                    return $this->canNonKycActivatedMerchantAccessPrivateXRoutes();
                });

            if ($isPrivateXRouteAccessible === true)
            {
                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null,
                PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST);
        }
    }

    /**
     * This is to check if a non kyc activated merchant can access private banking routes.
     * If a merchant is ca activated, then they can access private banking routes
     * @return boolean
     */
    public function canNonKycActivatedMerchantAccessPrivateXRoutes(): bool
    {
        $route = $this->app['api.route']->getCurrentRouteName();

        $isBankingRouteAndPrivate = $this->isBankingRouteAndPrivate($route);
        $isPayoutLinksPublicRoute = $this->isPayoutLinksPublicRoutes($route);

        if (! ($isBankingRouteAndPrivate || $isPayoutLinksPublicRoute))
        {
            return false;
        }

        $isMerchantCaActivated = Tracer::inspan(['name' => HyperTrace::AUTH_CRED_IS_CURRENT_ACCOUNT_ACTIVATED], function ()
        {
            return (new Merchant\Core())->isCurrentAccountActivated($this->merchant);
        });

        $isMerchantVaActivated = Tracer::inspan(['name' => HyperTrace::AUTH_CRED_IS_X_VA_ACTIVATED], function ()
        {
            return (new Merchant\Core())->isXVaActivated($this->merchant);
        });

        if ($isMerchantCaActivated === true || $isMerchantVaActivated === true)
        {
            if ($isBankingRouteAndPrivate === true)
            {
                $this->trace->count(Metric::PRIVATE_X_ROUTE_HITS_BY_CA_ACTIVATED_MERCHANT_COUNT);

                return true;
            }

            if ($isPayoutLinksPublicRoute === true)
            {
                $this->trace->count(Metric::PUBLIC_X_PAYOUT_LINKS_ROUTE_HITS_BY_CA_ACTIVATED_MERCHANT_COUNT);

                return true;
            }
        }

        return false;
    }

    public function isBankingRouteAndPrivate(string $route): bool
    {
        return in_array($route, Route::PRIVATE_BANKING_ROUTES, true);
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

        $this->app['basicauth']->setPassportCredentialClaims($this->key, $publicKey);
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;

        $this->app['rzp.mode'] = $mode;

        $this->app['basicauth']->setPassportMode($mode);
    }

    public function invalidApiKey()
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_API_KEY, [self::KEY_ID => $this->getKey()]);

        return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
    }

    public function isPayoutLinksPublicRoutes(string $route): bool
    {
        return in_array($route, Route::PAYOUT_LINKS_SPECIFIC_PUBLIC_ROUTES, true);
    }
}
