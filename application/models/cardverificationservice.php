<?php

/**
 * Provides card verification services.
 */
class CardVerificationService
{
	private static $card_attribute_rules = array(
		'number' => 'required|match:/[0-9]/',
		'expiry_month' => 'required|match:/[0-9]{2}/',
		'expiry_year' => 'required|match:/[0-9]/|min:2|max:4',
		'cvv' => 'required|match:/[0-9]/|size:3',
		'cardholder' => 'required|match:/[a-zA-Z *]/'
	);

	public static function attributes_check($data)
	{
        var_dump($data);
		$validation = Validator::make($data, self::$card_attribute_rules);
		if ($validation->fails()) 
		{
			return $validation->errors;
		}
	}

	public static function cvv_check($data)
	{
		return array(null, 1);
	}

	public static function address_line1_check($data, &$err)
	{
		return 1;
	}

	public static function address_zip_check($data, &$err)
	{
		return 1;
	}
}