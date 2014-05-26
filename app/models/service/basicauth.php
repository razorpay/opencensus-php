<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class BasicAuth extends \Singleton {
    
    private $Key = null;

    private $Merchant = null;

    public function verify($key)
    {
        if ($key === null)
        {
            throw new \InvalidArgumentException('NULL not an accepted key');
        }

        $Key = DAL\Key::find($key);
        
        if ($Key === null)
        {
            return false;
        }
        else if ($Key->active == 0)
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

    public function authenticate($credentials)
    {   

        if ($credentials === null || !isset($credentials['id']) || !isset($credentials['hash']))
        {
            throw new \InvalidArgumentException('Invalid Credentials');
        }

        $merchant_id = $credentials['id'];
        $hash = $credentials['hash'];
        
        $time = substr($hash, 64);
        $hash = substr($hash , 0, 64);

        //Time difference between merchant & our gateway shouldn't be more than 30 mins (Allowing for user to fill details)
        if($time > time()+1800 || $time < time()-1800) return false;

        //Hash once used is not allowed
        if(DAL\Hash::find($hash)) return false;

        $merchant = DAL\Merchant::find($merchant_id);

        if(null == $merchant)
        {
            throw new \InvalidArgumentException("Invalid Merchant Id");
        }

        $keys = DAL\Key::where('merchant_id', '=', $merchant_id)->where('active', '=', '1')->get();
        
        foreach($keys as $key)
        {
            $hash_stored = hash_hmac('sha256', $time, $key->id);

            if(md5($hash)==md5($hash_stored))
            {
                $this->key = $key;

                $this->Merchant = $merchant;

                $hash = DAL\Hash::create(array('hash'=>$hash));

                return true;
            }
        }
        return false;
    }

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

    public function verifySecret($key = null)
    {
        return (($this->verify($key)) and
                ($this->secret()));
    }

    public function secret()
    {
        return (($this->check()) and
                ($this->Key->secret));
    }

    public function verifyPublic($key = null)
    {
        return !$this->verifySecret($key);
    }

}