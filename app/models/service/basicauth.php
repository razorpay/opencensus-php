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

		$Key = DAL\Key::findByKey($key);
		
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