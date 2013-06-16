<?php

class CardService
{
	private $data = true;

	public function __construct($data = null)
	{
		$this->data = $data;
	}

	public function generate_token()
	{
		$data = $this->data;

		$new_card = new Card();

		$err = $new_card->verify_and_save($data);

		if ($err !== null)
		{
			return array(false, $err);
		}

		list($token, $err) = CardToken::generate($new_card->id, $data['merchant_id']);

		if ($token === false)
			return array($token, $err);

		$err = $new_card->verify_cvv();

		if ($err !== null)
		{
			$token->set_token_used();
		}

		return $err;
	}

	public function txn($data)
	{
		$da['$cvv_check'] = CardVerification::cvv_check($data, $err);
		if($err !== null)
		{
			; // Throw error.
		}

		if (isset($data['address_line1']))
		{
			$data['$address_line1_check'] = CardVerification::address_line1_check($data, $err);
			if($err !== null)
			{
				; // Throw error.
			}
		}
		
		if (isset($data['address_zip']))
		{
			$data['$address_zip_check'] = CardVerification::address_zip_check($data, $err);
			if($err !== null)
			{
				; // Throw error.
			}
		}

		$data['type'] = CardVerification::determine_type($data, $err);
		if($err !== null)
		{
			; // Throw error.
		}

		$data['conuntry'] = CardVerification::determine_country($data, $err);
		if($err !== null)
		{
			; // Throw error.
		}
	}
	
}