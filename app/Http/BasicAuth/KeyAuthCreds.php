<?php

namespace RZP\Http\BasicAuth;

use Crypt;
use ApiResponse;

use RZP\Constants\HyperTrace;
use RZP\Models\Key;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class KeyAuthCreds extends AuthCreds
{
    public function isKeyExisting()
    {
        $keyId = $this->getKey();

        if ($keyId === '')
        {
            return false;
        }

        $this->fetchKey($keyId);

        return (empty($this->key) === false);
    }

    public function verifyKeyNotExpired()
    {
        $valid = ((empty($this->key) === false) and ($this->key->isExpired() === false));

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

        $keyId = substr($key, 9);

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
            //We are setting passport claims here to identify consumer, as edge identifies consumer even if authentication fails
            $this->app['basicauth']->setPassportConsumerClaims(BasicAuth::PASSPORT_CONSUMER_TYPE_MERCHANT, $this->key->getMerchantId(), false);

            $this->trace->info(TraceCode::BAD_REQUEST_API_SECRET_NOT_PROVIDED, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $keyEntity = $this->key;

        $decryptedSecret = Tracer::inspan(['name' => HyperTrace::KEY_AUTH_CRED_GET_DECRYPTED_SECRET], function ()  use ($keyEntity)
        {
            return $keyEntity->getDecryptedSecret();
        });

        if ($decryptedSecret !== $secret)
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_INVALID_API_SECRET, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }

        $this->fetchAndSetMerchantAndCheckLive();

        return true;
    }

    public function fetchAndSetMerchantAndCheckLive()
    {
        $merchantId = $this->key->getMerchantId();

        $merchant = Tracer::inspan(['name' => HyperTrace::KEY_AUTH_CRED_MERCHANT], function () use ($merchantId)
        {
            return $this->repo->merchant->findOrFail($merchantId);
        });

        Tracer::inspan(['name' => HyperTrace::KEY_AUTH_CRED_SET_AND_CHECK_MERCHANT_ACTIVATED_FOR_LIVE], function () use ($merchant)
        {
            return $this->setAndCheckMerchantActivatedForLive($merchant);
        });

        return $this->merchant;
    }

    public function getKeyEntity()
    {
        return $this->key;
    }

    protected function fetchKey($keyId)
    {
        //
        // If key entity has already been resolved in request.ctx use that to avoid another redis call.
        // Ideally if key entity is not resolved in request.ctx then probably key id doesn't exists in http request.
        // But to be on safe side, in case some cases are not handled in request.ctx and keyId exists here in this flow
        // continue with cache(fallback db query).
        //
        $this->key = Tracer::inspan(['name' => HyperTrace::KEY_AUTH_CRED_IS_KEY_EXISTING], function ()  use ($keyId)
        {
            return $this->reqCtx->getKeyEntity() ?: $this->repo->key->find($keyId);
        });

        return $this->key;
    }
}
