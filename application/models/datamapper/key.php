<?php namespace DataMapper;

class Key extends \Eloquent {

	private $key_generated = NULL;

	private static $key_row_rules = array(
		'live' => 'required|size:1|in:0,1',
		'secret' => 'required|size:1|in:0,1');

	public function generate($data, &$error)
	{
		$validation = Validator::make($data, self::$key_row_rules);
		
		if ($validation->fails())
		{
			$error = $validation->errors;
			return false;
		}

		$this->keys = bin2hex(openssl_random_pseudo_bytes(16));
		$this->live = $data['live'];
		$this->secret = $data['secret'];
		$this->merchant_id = $data['merchant_id'];
		$this->active = 1;
		return $this->save();
	}

	public function merchant()
	{
		return $this->belongs_to('Merchant');
	}

	public static function find_by_key($key)
	{
		$key = Key::where('keys', '=', $key)->first();
		
		return $key;
	}

	private static function generate_key()
	{
		return bin2hex(openssl_random_pseudo_bytes(16));
	}
}