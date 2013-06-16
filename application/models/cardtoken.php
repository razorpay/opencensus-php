<?php

class CardToken extends Eloquent {

	public static $hidden = array('id','card_id');

	public function card()
	{
		return $this->belongs_to('Card');
	}

	/**
	 * Generates a new token referencing credit card id.
	 * @param  array $data  [description]
	 * @return array/null $error	    [description]
	 */
	public static function generate($card_id, $merchant_id)
	{
		
		try 
		{
			$token = CardToken::create(array(
				'card_id' => (int) $card_id,
				'merchant_id' => (int) $merchant_id,
				'token' => self::generate_card_token(),
				'expired' => 0));
		}
		catch (\Exception $e) {
			return array(false, $e->getMessage());
		}

		return array($token, null);
	}

	private static function generate_card_token ()
	{
		return bin2hex(openssl_random_pseudo_bytes(32));
	}

	private function set_attr($key, $value)
	{
		$this->{$key} = $value;
	}

	public function set_token_used()
	{
		$this->expire = 1;
		try
		{
			$this->save();
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}
	}
}

?>