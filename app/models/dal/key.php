<?php 

namespace Models\DAL;

use \Validator;

class Key extends DAL
{

	protected $table  = 'keys';

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
		return $this->belongsTo(
			__NAMESPACE__.'\Merchant');
	}

	public static function findByKey($key)
	{
		$key = Key::where('keys', '=', $key)->first();
		
		return $key;
	}

	private static function generateKey()
	{
		return bin2hex(openssl_random_pseudo_bytes(16));
	}
}