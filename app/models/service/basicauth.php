<?php

namespace Service;

use DataMapper\Key;
use DataMapper\Merchant;

class BasicAuth extends \Singleton {
	
	private $Key = NULL;

	private $Merchant = NULL;

	public function verify($key)
	{
		if ($key === null)
		{
			throw new \InvalidArgumentException('NULL not an accepted key');
		}

		$Key = Key::findByKey($key);
		
		if ($Key === null)
		{
			return false;
		}
		else if ($Key->active == 0)
		{
			return false;
		}
		
		$merchant_id = $Key->merchant_id;
		
		$Merchant = Merchant::find($merchant_id);

		if(null == $Merchant)
		{
			throw new \InvalidArgumentException("Key does not match any merchant");
		}

		self::$Key = $Key;

		self::$Merchant = $Merchant;

		return true;
	}

	public function check()
	{
		if ((!is_null(self::$Key)) and
			(!is_null(self::$Merchant)))
			return true;
		else
			return false;	
	}

	public function Key()
	{
		return self::$Key;
	}

	public function Merchant()
	{
		return self::$Merchant;
	}

	public function MerchantId()
	{
		return (int) self::$Merchant->id;
	}

	public function live()
	{
		return self::$Key->live;
	}

	public function verifySecret($key = null)
	{
		return ((self::verify($key)) and
				(self::secret());
	}

	public function secret()
	{
		return ((self::check()) and
			    (self::$Key->secret));
	}

	public function verifyPublic($key = null)
	{
		return !self::verifySecret($key);
	}

}