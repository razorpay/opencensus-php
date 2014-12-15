<?php

namespace Models\Key;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Key;
use Models\Merchant;

class Core
{
    public function createFirstKey($merchantId, $mode)
    {
        $repo = new Key\Repository;

        $keys = $repo->getKeysForMerchant($merchantId);

        if (count($keys) > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED);
        }

        return $this->createAndReturnWithSecret($merchantId, $mode);
    }

    /**
     * Creates a key and saves to db.
     * Retruns an array with key data and secret
     * in plain text
     *
     * @param  int    $merchantId
     * @return array
     */
    public function createAndReturnWithSecret($merchantId, $mode)
    {
        $repo = new Key\Repository;

        $key = new Key\Entity();

        $key->setMerchantId($merchantId);

        // Generate secret which will be returned to merchant
        $secret = $key->generateSecret();

        $repo->saveOrFail($key);

        $keyData = $key->toArrayPublic();
        $keyData[Key\Entity::SECRET] = $secret;

        return $keyData;
    }

    public function expireKey(Key\Entity $key, $delay)
    {
        $key->checkAndSetExpired($delay);

        (new Key\Repository)->saveOrFail($key);
    }

    public function rollKey($keyId, array $input, $mode)
    {
        Key\Entity::verifyIdAndStripSign($keyId);

        Key\Validator::checkForDemoKeys($keyId);

        $old = (new Key\Repository)->findOrFailPublic($keyId);

        $delay = false;

        if (isset($input['delay_roll']))
        {
            $delay = ($input['delay_roll'] === '1') ? true : false;
        }

        unset($input['delay_roll']);

        $this->expireKey($old, $delay);

        $keyData = $this->createAndReturnWithSecret($old->getMerchantId(), $mode);

        $keysData['old'] = $old->toArrayPublic();

        $keysData['new'] = $keyData;

        return $keysData;
    }
}