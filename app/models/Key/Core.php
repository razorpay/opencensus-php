<?php

namespace Models\Key;

use Constants\Mode;
use Crypt;
use EE\Exception;
use EE\Error\ErrorCode;
use Models\Key;
use Models\Merchant;

class Core
{
    public function createFirstKey($merchant, $mode)
    {
        $repo = new Key\Repository;

        $keys = $repo->getKeysForMerchant($merchant->getId());

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
        $repo = new Key\Repository;

        $key = new Key\Entity();

        if (($mode === Mode::LIVE) and
            ($merchant->isActivated() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED);

        }

        $key->setMerchantId($merchant->getId());

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

        $keyData = $this->createAndReturnWithSecret($old->merchant, $mode);

        $keysData['old'] = $old->toArrayPublic();

        $keysData['new'] = $keyData;

        return $keysData;
    }

    public function getKeySecret($keyId)
    {
        Key\Entity::verifyIdAndStripSign($keyId);

        $key = (new Key\Repository)->findOrFailPublic($keyId);

        $secret = Crypt::decrypt($key->getSecret());

        return ['secret' => $secret];
    }
}