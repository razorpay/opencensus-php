<?php

class BasicAuth {
	
	private static $Key = NULL;

	private static $Merchant = NULL;

	public static function verify_key()
	{
		if(isset($_SERVER['PHP_USER_AUTH']))
		{
			$key = $_SERVER['PHP_USER_AUTH'];


			if (!is_null($Key))
			{
				self::$Key = Key::find($key);

				if(self::$Key->active == 0)
					return;
				
				$merchant_id = self::$Key->merchant_id;
				$Merchant = Merchant::find($merchant_id);
				if(!is_null($Merchant))
				{
					self::$Merchant = $Merchant;
				}
			}
		}
	}

	public static function check()
	{
		if ((!is_null($Key)) &&
			(!is_null($Merchant)))
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

}