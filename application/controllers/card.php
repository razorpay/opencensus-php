<?php

class Card_Controller extends Base_Controller 
{

	public $restful = true;

	private function getAuthenticatedMerchant() {
		//TODO: get the merchant's `id` authenticated via key.
		return 1;
	}

	/**
	* To create a new card object. Retrieve one-time use card token.
	*/
	public function post_index ()
	{
		$merchantId = $this->getAuthenticatedMerchant();

		$m = Merchant::find($merchantId);
		if ($m === NULL)
			return Response::error('401');

		$number = trim(Input::get('number'));

		$card = Card::where('number','=',$number)->first();

		if ($card !== NULL) {
			$r = CardToken::where('card_id','=',$card->id)->update(array('expired'=>1));
			$t = new CardToken();
			$t->buildCardToken($card->id);
			$t = CardToken::where('card_id','=',$card->id)->first();
		}

		else {
			$c = new Card();
			$e = $c->buildCard(Input::get());
			if ($e !== NULL)
				return Response::json($e);
			$t = new CardToken();
			$t->buildCardToken($c->id);
			$t = CardToken::where('card_id','=',$c->id)->first();
		}

		return Response::eloquent($t);
		
	}

}