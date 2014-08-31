<?php

namespace Models\Key;

use Models\Key;

class Core
{
    protected $key = null;

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
        $key = new Key\Entity();
        $key->setMerchantId($merchantId);

        $secret = $key->generateSecret();
        (new Key\Repository)->saveOrFail($key);

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