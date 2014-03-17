<?php 

namespace Models\DAL;

use \Validator;

class Key extends DAL
{
	protected $fillable = array();

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

		$this->attributes['id'] = bin2hex(openssl_random_pseudo_bytes(16));
		$this->attributes['live'] = $data['live'];
		$this->attributes['secret'] = $data['secret'];
		$this->attributes['merchant_id'] = $data['merchant_id'];
		$this->attributes['active'] = 1;
		return $this->save();
	}

	public function merchant()
	{
		return $this->belongsTo(
			__NAMESPACE__.'\Merchant');
	}

	private static function generateKey()
	{
		return bin2hex(openssl_random_pseudo_bytes(16));
	}
}