<?php

namespace Models\Service;

use Models\Key;
use Models\Merchant;
use EE\Exception\InvalidArgumentException;

class BasicAuth extends \Singleton {

    private $key = null;

    private $merchant = null;

    private $App = null;

    public function check()
    {
        //
        // If either of key or merchant is null,
        // return false
        // If both are present, return true
        //
        return (($this->key !== null) and
                ($this->merchant !== null));

    }

    public function getKey()
    {
        return $this->key;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }

    public function getMerchantId()
    {
        $id = $this->merchant->getKey();

        return (int) $id;
    }

    public function live()
    {
        return $this->key->live;
    }

    public function verifySecret($keyId, $keySecret)
    {
        $key = (new Key\Repository)->findNotExpired($keyId);

        if ($key === null)
        {
            return false;
        }

        if ($key->active == 0)
        {
            return false;
        }

        $check = \Hash::check($keySecret, $key->getSecret());

        if ($check === false)
        {
            return false;
        }

        $merchantId = $key->getMerchantId();

        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $this->key = $key;

        $this->merchant = $merchant;

        return true;
    }

    public function verifyPublic($keyId)
    {
        $key = (new Key\Repository)->find($keyId);

        if ($key === null)
        {
            return false;
        }
        else if ($key->active == 0)
        {
            return false;
        }

        $merchantId = $key->getMerchantId();

        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $this->key = $key;

        $this->merchant = $merchant;

        return true;
    }

    public function verifyApp($merchantId, $secret)
    {
        $verify = false;

        foreach (\Config::get('applications') as $name => $app)
            if ($app['auth_pass'] === $secret)
            {
                $verify = true;
                $this->App = $name;
                break;
            }

        if ($verify === false)
            return false;

        $this->merchant = (new Merchant\Repository)->find($merchantId);

        if ($this->merchant === null)
            return false;

        return true;
    }
}