<?php

namespace RZP\Http\BasicAuth;

use Crypt;
use ApiResponse;

use RZP\Models\Key;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class KeyAuthCreds extends AuthCreds
{

    protected $orgCustomCode = null;

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
            $this->trace->info(TraceCode::BAD_REQUEST_API_SECRET_NOT_PROVIDED, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $keyEntity = $this->key;

        if ($keyEntity->getDecryptedSecret() !== $secret)
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

    public function setMerchant($merchant)
    {
        if ($merchant !== null)
        {
            /** @var Org\Entity $org */
            $org = $merchant->org;

            $this->setOrgCustomCode($org->getCustomCode());
        }
        parent::setMerchant($merchant);
    }

    public function setOrgCustomCode($orgCustomCode)
    {
        $this->orgCustomCode = $orgCustomCode;

        return $this;
    }

    public function getOrgCustomCode()
    {
        return $this->orgCustomCode;
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
        $this->key = $this->reqCtx->getKeyEntity() ?: $this->repo->key->find($keyId);

        return $this->key;
    }
}
