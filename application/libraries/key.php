<?php 

class Key {
	
	/**
     * Key is test/live
     */
	private $live;

	/**
	 * Key is published/secret.
	 */
	private $secret;

	/**
	 * Key is generated/provided.
	 */
	private $generated;

	/**
	 * Key
	 */
	private $key;

	public function __construct()
	{
		if (isset($_SERVER['PHP_AUTH_USER']))
		{
			$key = $_SERVER['PHP_AUTH_USER'];
			$generated = false;
		}
		else
		{
			$key = openssl_random_pseudo_bytes(32);
			$generated = true;
		}
	}

	public function is_secret()
	{
		return $secret;
	}

	public function is_public()
	{
		return !$secret;
	}

	public function is_live()
	{
		return $live;
	}

	public function is_test()
	{
		return !$live;
	}

	public function is_generated()
	{
		return $generated;
	}
}