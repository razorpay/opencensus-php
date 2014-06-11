<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class BasicAuth extends \Singleton {

    private $Key = null;

    private $Merchant = null;

    private $App = null;

    private static $Apps = array(
        'dashboard' =>  array(
            'id'        =>  'd4$hb04rd',
            'secret'    =>  'a128a3994372ccd2a63a8a64202a92e04eb83e54'
        )
    );

    public function check()
    {
        if (($this->Key == null) or
            ($this->Merchant == null))
            return false;
        else
            return true;
    }

    public function Key()
    {
        return $this->Key;
    }

    public function Merchant()
    {
        return $this->Merchant;
    }

    public function MerchantId()
    {
        return (int) $this->Merchant->id;
    }

    public function live()
    {
        return $this->Key->live;
    }

    public function verifySecret($key_id = null, $key_secret = null)
    {
        if ($key_id === null || $key_secret === null )
        {
            throw new \InvalidArgumentException('Invalid Key Details');
        }

        $Key = DAL\Key::find($key_id);

        if ($Key === null)
        {
            return false;
        }
        else if ($Key->active == 0)
        {
            return false;
        }
        else if (! \Hash::check($key_secret, $Key->secret))
        {
          return false;
        }

        $merchant_id = $Key->merchant_id;

        $Merchant = DAL\Merchant::find($merchant_id);

        if(null == $Merchant)
        {
            throw new \InvalidArgumentException("Key does not match any merchant");
        }

        $this->Key = $Key;

        $this->Merchant = $Merchant;

        return true;
    }

    public function verifyPublic($key_id = null)
    {
        if ($key_id === null)
        {
            throw new \InvalidArgumentException('Invalid Credentials');
        }

        $Key = DAL\Key::find($key_id);

        if ($Key === null)
        {
            return false;
        }
        else if ($Key->active == 0)
        {
            return false;
        }
        
        $merchant_id = $Key->merchant_id;

        $Merchant = DAL\Merchant::findOrFail2($merchant_id);

        if(null == $Merchant)
        {
            throw new \InvalidArgumentException("Key does not match any merchant");
        }

        $this->Key = $Key;

        $this->Merchant = $Merchant;

        return true;
    }

    public function verifyApp($key_id = NULL, $key_secret = NULL)
    {
        if ($key_id === NULL || $key_secret === NULL)
            throw new \InvalidArgumentException('Invalid Key Details');

        $verify = false;

        foreach (static::$Apps as $name => $credentials)
            if (    $credentials['id'] === $key_id &&
                    $credentials['secret'] === $key_secret
                )
            {
                $verify = true;
                $this->App = $name;
                break;
            }


        if ($verify === false)
            return false;

        $input = \Input::all();

        if (!isset($input['merchant_id']))
            return false;
        else
            $merchant_id = $input['merchant_id'];

        $this->Merchant = DAL\Merchant::find($merchant_id);

        unset($input['merchant_id']);

        return true;
    }

}
