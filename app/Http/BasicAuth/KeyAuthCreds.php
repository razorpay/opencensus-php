<?php

namespace RZP\Http\BasicAuth;

use Crypt;
use ApiResponse;

use RZP\Models\Key;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

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

        $keyId = $keyId ?? '';

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

        $keyEntity = $this->key;

        if (Crypt::decrypt($keyEntity->getSecret()) !== $secret)
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

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->setAndCheckMerchantActivatedForLive($merchant);

        return $this->merchant;
    }

    public function getKeyEntity()
    {
        return $this->key;
    }

    protected function fetchKey($keyId)
    {
        $this->key = $this->repo->key->find($keyId);

        return $this->key;
    }
}