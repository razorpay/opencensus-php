<?php

namespace RZP\Http\BasicAuth;

use ApiResponse;
use Razorpay\OAuth\Client as OAuthClient;
use Razorpay\OAuth\Application as OAuthApp;

use RZP\Constants\HyperTrace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class ClientAuthCreds extends AuthCreds
{
    const CLIENT_ID = 'client_id';

    /**
     * Used instead of api key for partner authentication
     * @var OAuthClient\Entity
     */
    protected $partnerClient = null;

    /**
     * Partner's OAuth application id
     * @var null
     */
    protected $partnerApplicationId = null;

    /**
     * Client types are interpreted differently in API vs
     * auth-service. We store the mapping here. API uses
     * test and live and restricts them the test/live modes
     * respectively. Auth-service refers to these as dev and
     * prod and the interpretation for Pure-platforms there
     * is not related to these modes from API.
     */
    public static $clientModes = [
        'test' => 'dev',
        'live' => 'prod',
    ];

    public function isKeyExisting()
    {
        $keyId = $this->getKey();

        if ($keyId === '')
        {
            return false;
        }

        try
        {
            $this->partnerClient = Tracer::inspan(['name' => HyperTrace::CLIENT_AUTH_CRED_IS_KEY_EXISTING], function ()  use ($keyId)
            {
                return  (new OAuthClient\Repository)->getClientByIdAndEnv(
                    $keyId,
                    self::$clientModes[$this->getMode()]
                );
            });


            $this->partnerApplicationId = $this->partnerClient->getApplicationId();
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::BAD_REQUEST_INVALID_CLIENT_KEY, [self::CLIENT_ID => $this->getKey()]);
        }

        return (empty($this->partnerClient) === false);
    }

    public function verifyKeyNotExpired()
    {
        $valid = (empty($this->partnerClient) === false);

        if ($valid !== true)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED);
        }

        return true;
    }

    public function validateAndSetKeyId(string $key)
    {
        if (($this->verifyKeyLength($key) === false) or
            ($this->verifyKeyPrefix($key) === false) or
            ($this->verifyAndSetMode($key) === false))
        {
            return $this->invalidApiKey();
        }

        //
        // rzp_test_partner_A0jg73G43ihI90
        //
        $keyId = substr($key, -14);

        $keyId = $keyId ?: '';

        $this->creds[self::KEY_ID] = $keyId;
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

        $client = $this->partnerClient;

        if ($client->getSecret() !== $secret)
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_INVALID_API_SECRET, ['client_id' => $client->getSecret()]);

            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }

        $this->fetchAndSetMerchantAndCheckLive();

        return true;
    }

    /**
     * This function does not actually check for live mode since this is on the partner merchant
     * and partner's access to live mode doesn't matter while accessing submerchant resources.
     *
     * @return \RZP\Models\Merchant\Entity
     */
    public function fetchAndSetMerchantAndCheckLive()
    {
        $merchantId = $this->partnerClient->getMerchantId();

        $merchant = Tracer::inspan(['name' => HyperTrace::CLIENT_AUTH_CRED_MERCHANT], function () use ($merchantId)
        {
            return $this->repo->merchant->findOrFail($merchantId);
        });

        $this->setMerchant($merchant);

        return $this->merchant;
    }

    public function getPartnerClient()
    {
        return $this->partnerClient;
    }

    public function getPartnerApplicationId()
    {
        return $this->partnerApplicationId;
    }

    /**
     * This function is used when a partner merchant uses partner credentials without submerchant account,
     * in which case we want the behaviour to be exactly as privateAuth, so we are unsetting PartnerClient
     */
    public function unsetPartnerClient()
    {
        $this->partnerClient = null;
    }

    public function unsetPartnerApplicationId()
    {
        $this->partnerApplicationId = null;
    }
}
