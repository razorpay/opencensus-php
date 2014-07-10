<?php

namespace Models\Key;

use Models\Key;

class Core
{
    protected $key = null;

    public function create($merchantId)
    {
        $input['merchant_id'] = $merchantId;

        $key = (new Key\Entity)->build($input);

        return $key;
    }

    /**
     * Creates a key and saves to db.
     * Retruns an array with key data and secret
     * in plain text
     *
     * @param  int    $merchantId
     * @return array
     */
    public function createAndReturnWithSecret($merchantId)
    {
        $key = $this->create($merchantId);

        $secret = $key->generateSecret();

        (new Key\Repository)->saveOrFail($key);

        $array = $key->toArray();

        $array[Key\Entity::SECRET] = $secret;

        return $array;
    }

    public function setExpired(Key\Entity $key, $delay)
    {
        $key->checkAndSetExpired($delay);

        (new Key\Repository)->saveOrFail($key);
    }
}