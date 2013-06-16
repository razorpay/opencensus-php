<?php

class Card_Controller extends Base_Controller 
{

	public $restful = true;

	/**
	* To create a new card object. Retrieve one-time use card token.
	*/
	public function post_index ()
	{
		$data = Input::get();
		$data['merchant_id'] = BasicAuth::MerchantId();

		$service = new CardService($data);

		list($token, $err) = $service->generate_token();

		if ($err !== null)
			var_dump($err);
		else
			Response::eloquent($token);
	}

	

}