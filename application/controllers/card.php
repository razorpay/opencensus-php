<?php

use Service\Token;
use Service\BasicAuth;

class Card_Controller extends Base_Controller 
{

	public $restful = true;

	/**
	 * To create a new card object. Retrieve one-time use card token.
	 */
	public function post_index()
	{
		$input = Input::get();
		
		$token_service = new Token();

		list($token_data, $err) = $token_service->generate($input);

		if ($err !== ERR::SUCCESS)
			ERR::print_last_error();
		else
		{
			return Response::json($token_data);
		}
	}

	public function get_retrieve($token)
	{
		$token_service = new Token;

		$merchant_id = BasicAuth::MerchantId();

		list($token_data, $err) = $token_service->retrieve($token, $merchant_id);
		
		if (($token_data === false) or
			($err !== ERR::SUCCESS))
		{
			ERR::print_last_error();
		}

		return Response::json($token_data);
	}

	

}