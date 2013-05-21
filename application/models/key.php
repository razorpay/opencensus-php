<?php 

class Key extends Eloquent {

	

	public function __construct()
	{
		$key = openssl_random_pseudo_bytes(32);
		$generated = true;
	}

	public function __construct($key)
	{

	}

	public function __construct($key, $live, $secret)
	{
		this->$key = $key;
		this->$live = $live;
		this->$secret = $secret;
		this->$generated = false;
	}

	public static function http_basic_auth_key()
	{
		if (!isset($_SERVER['PHP_AUTH_USER']))
		{
			return false;
		}

		$key = $_SERVER['PHP_AUTH_USER'];
		
		Key::where('public', '=', $key)
			->or_where('private' '=', $key);
	}

	public function is_secret()
	{
		return (($generated) ? -1 : $secret);
	}

	public function is_public()
	{
		return !(this->is_secret());
	}

	public function is_live()
	{
		return (($generated) ? -1 : $live);
	}

	public function is_test()
	{
		return !(this->is_live());
	}

	public function is_generated()
	{
		return $generated;
	}
}