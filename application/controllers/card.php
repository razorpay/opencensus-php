<?php

class Card_Controller extends Base_Controller 
{

	public $restful = true;

	private function get_authenticated_merchant() {
		//TODO: get the merchant's `id` authenticated via key.
		return 1;
	}

	/**
	* To create a new card object. Retrieve one-time use card token.
	*/
	public function post_index ()
	{
		$merchant_id = $this->get_authenticated_merchant();

		$m = Merchant::find($merchant_id);
		if ($m === NULL)
			return Response::error('401');

		$c = new Card();
		$e = $c->validate_attributes(Input::get());
		if ($e !== NULL)
			return $e;

		$card = Card::where('number','=',Input::get('number'))->first();

		if ($card !== NULL) {
			if ( $card->expiry_month != Input::get('expiry_month') || $card->expiry_year != Input::get('expiry_year') || $card->cvv != Input::get('cvv') )
				return Response::error('400');
			$r = CardToken::where('card_id','=',$card->id)->update(array('expired'=>1));
			$t = new CardToken();
			$e = $t->build_cardtoken($card->id);
			if ($e !== NULL)
				return $e;
			$t = CardToken::where('card_id','=',$card->id)->first();
		}

		else {
			$e = $c->build_card(Input::get());
			if ($e !== NULL)
				return $e;
			$t = new CardToken();
			$t->build_cardtoken($c->id);
			$t = CardToken::where('card_id','=',$c->id)->first();
		}

		return Response::eloquent($t);
		
	}

}