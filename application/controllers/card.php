<?php

use Service\Token;

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

		list($token_do, $err) = $token_service->generate($input);

		if ($err !== null)
			var_dump($err);
		else
			;//Response::eloquent($token);
	}

    public function get_retrieve($token)
    {
        $token_service = new Token;

        $merchant_id = BasicAuth::MerchantId();

        list($token_data, $err) = $token_service->retrieve($token, $merchant_id);
        
        if (($token_data === false) or
        	($err !== ERR::SUCCESS))
        {
        	echo $err . " is the error code returned.";
        	return Response::error('400');
        }

        return Response::json($token_data);
    }

	

}