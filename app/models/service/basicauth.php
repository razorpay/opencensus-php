<?php

namespace Models\Service;

use Models\Key;
use Models\Merchant;

class BasicAuth extends \Singleton
{
    /**
     * Key used for authentication
     * @var Key\Entity
     */
    private $key = null;

    /**
     * Merchant who is being authenticated
     * either by himself or by an internal
     * application
     *
     * @var Merchant\Entity
     */
    private $merchant = null;

    private $app = null;

    /**
     * Checks if given key id is present
     * in database or not. Only non-expired keys
     * are checked. If there, then it's secret
     * is matched against the one provided.
     *
     * Used for private/secret authentication.
     * These requests are expected to originate
     * from merchant's server
     *
     * @param  string   $keyId
     * @param  string   $keySecret
     * @return boolean
     */
    public function verifySecret($keyId, $keySecret)
    {
        $key = $this->fetchKey($keyId);

        if ($key === null)
        {
            return false;
        }

        $check = $this->matchSecret($keySecret, $key);

        if ($check === false)
        {
            return false;
        }

        $this->fetchMerchantOfKey($key);

        return true;
    }

    /**
     * Checks if given key id is present in
     * database or not. Only non-expired keys
     * are checked.
     *
     * Used for public authentication
     *
     * @param  string  $keyId
     * @return boolean
     */
    public function verifyPublic($keyId)
    {
        $key = $this->fetchKey($keyId);

        if ($key === null)
        {
            return false;
        }

        $this->fetchMerchantOfKey($key);

        return true;
    }

    /**
     * Matches the secret provided against the list of
     * applications secrets with us. If any matches, then
     * that  particular app is allowed to continue
     * it's operation.
     *
     * If a merchant id is provided, then the app is
     * authenticating as that merchant and trying to
     * perform operations related to that merchant.
     *
     * Used for public authentication
     *
     * @param  string  $merchantId
     * @param  string  $secret
     * @return boolean
     */
    public function verifyApp($merchantId, $secret)
    {
        $verify = $this->verifyAppSecret($secret);

        if ($verify === false)
        {
            return false;
        }

        $this->merchant = (new Merchant\Repository)->find($merchantId);

        return true;
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
        if ($this->merchant === null)
            return;

        $id = $this->merchant->getKey();

        return (int) $id;
    }

    protected function fetchKey($keyId)
    {
        $this->key = (new Key\Repository)->findNotExpired($keyId);

        return $this->key;
    }

    protected function fetchMerchantOfKey($key)
    {
        $merchantId = $key->getMerchantId();

        $this->merchant = (new Merchant\Repository)->findOrFail($merchantId);

        return $this->merchant;
    }

    protected function matchSecret($keySecret, $key)
    {
        return \Hash::check($keySecret, $key->getSecret());
    }

    protected function verifyAppSecret($secret)
    {
        $apps = \Config::get('applications');

        foreach ($apps as $name => $app)
        {
            $match = $this->matchAppSecret($app, $secret);

            if ($match)
            {
                $verify = true;

                $this->app = $name;

                break;
            }
        }

        return $verify;
    }

    protected function matchAppSecret($app, $secret)
    {
        return ($app['auth_pass'] === $secret);
    }
}