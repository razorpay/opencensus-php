<?php

class BasicAuth {
	
	private static $Key = NULL;

	private static $Merchant = NULL;

	public static function verify_key()
	{
		if(isset($_SERVER['PHP_AUTH_USER']))
		{
			$key = $_SERVER['PHP_AUTH_USER'];
			
			self::$Key = Key::find_by_key($key);

			if(is_null(self::$Key or self::$Key->active == 0))
				return;
			
			$merchant_id = self::$Key->merchant_id;
			$Merchant = Merchant::find($merchant_id);
			if(!is_null($Merchant))
			{
				self::$Merchant = $Merchant;
			}
		}
	}

	public static function check()
	{
		if ((!is_null(self::$Key)) &&
			(!is_null(self::$Merchant)))
			return true;
		else
			return false;	
	}

	public static function Key()
	{
		return self::$Key;
	}

	public static function Merchant()
	{
		return self::$Merchant;
	}

	public static function live()
	{
		return self::$Key->live;
	}

	public static function secret()
	{
		return self::$Key->secret;
	}

}