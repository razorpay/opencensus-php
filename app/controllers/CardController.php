<?php

use Service\Token;
use Service\BasicAuth;

class CardController extends BaseController 
{

	/**
	 * To create a new card object. Retrieve one-time use card token.
	 */
	public function postIndex()
	{
		$input = Input::get();
		
		$token_service = new Token();

		list($token_data, $err) = $token_service->generate($input);

		if ($err !== ERR::SUCCESS)
			return ERR::handle_error();
		else
		{
			return Response::json($token_data);
		}
	}

	public function getRetrieve($token)
	{
		$token_service = new Token;

		$merchant_id = BasicAuth::MerchantId();

		list($token_data, $err) = $token_service->retrieve($token, $merchant_id);
		
		if (($token_data === false) or
			($err !== ERR::SUCCESS))
		{
			return ERR::handle_error();
		}

		return Response::json($token_data);
	}
}