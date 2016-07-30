<?php

namespace RZP\Models\Key;

use RZP\Constants\Mode;
use Crypt;
use RZP\Models\Base;
use RZP\Models\Key;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function createFirstKey($merchant, $mode)
    {
        $keys = $this->repo->key->getKeysForMerchant($merchant->getId());

        if (count($keys) > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED);
        }

        return $this->createAndReturnWithSecret($merchant, $mode);
    }

    /**
     * Creates a key and saves to db.
     * Retruns an array with key data and secret
     * in plain text
     *
     * @param  int    $merchantId
     * @return array
     */
    public function createAndReturnWithSecret($merchant, $mode)
    {
        $key = new Key\Entity();

        if (($mode === Mode::LIVE) and
            ($merchant->isActivated() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED);

        }

        $key->merchant()->associate($merchant);
        $key->build();

        // Generate secret which will be returned to merchant
        $secret = $key->generateSecret();

        $this->repo->saveOrFail($key);

        $keyData = $key->toArrayPublic();
        $keyData[Key\Entity::SECRET] = $secret;

        return $keyData;
    }

    public function expireKey(Key\Entity $key, $delay)
    {
        $key->checkAndSetExpired($delay);

        (new Key\Repository)->saveOrFail($key);
    }

    public function rollKey($merchantId, $keyId, array $input, $mode)
    {
        Key\Entity::verifyIdAndStripSign($keyId);

        Key\Validator::checkForDemoKeys($keyId);

        $old = $this->repo->key->findByMerchantIdAndKeyId($merchantId, $keyId);

        if ($old === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $delay = false;

        if (isset($input['delay_roll']))
        {
            $delay = ($input['delay_roll'] === '1') ? true : false;
        }

        unset($input['delay_roll']);

        $this->expireKey($old, $delay);

        $keyData = $this->createAndReturnWithSecret($old->merchant, $mode);

        $keysData['old'] = $old->toArrayPublic();

        $keysData['new'] = $keyData;

        return $keysData;
    }

    public function getKeySecret($keyId)
    {
        Key\Entity::verifyIdAndStripSign($keyId);

        $key = $this->repo->key->findOrFailPublic($keyId);

        $secret = Crypt::decrypt($key->getSecret());

        return ['secret' => $secret];
    }
}
