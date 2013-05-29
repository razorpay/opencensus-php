<?php

class Transaction_Controller extends Base_Controller 
{

	public $restful = true;

	private function getAuthenticatedMerchant() {
		//TODO: get the merchant's `id` authenticated via key.
		return 1;
	}

	/**
	* To retrieve transaction details by `id`
	* Lists previous transactions if `id` not provided
	*
	* @param id (optional)
	*
	*/
	public function get_index ( $token = NULL )
	{
		$merchantId = $this->getAuthenticatedMerchant();

		$m = Merchant::find($merchantId);
		if ($m === NULL)
			return Response::error('401');

		if ($token === NULL) {
			$t = $m->transactions;
			if ( empty($t) )
				return Response::error('404');
			else
				return Response::eloquent($t);
		}

		else {
			$t = Transaction::where('token','=',$token)->first();
			if ( $t !== NULL )
				if ( $t->getMerchant() === $merchantId )
					return Response::eloquent($t);
				else
					return Response::error('401');
			else
				return Response::error('404');
		}

	}

	/**
	* To create a new transaction. Retrieve transaction token.
	*/
	public function post_index ()
	{
		$merchantId = $this->getAuthenticatedMerchant();

		$m = Merchant::find($merchantId);
		if ($m === NULL)
			return Response::error('401');

		$t = new Transaction();
		$e = $t->buildTransaction(Input::get(), $merchantId);
		
		if ($e !== NULL)
			return $e;

		$t = Transaction::find($t->id);

		return Response::eloquent($t);
		
	}

	/**
	* To refund a transaction.
	*/
	public function post_refund ( $token = NULL )
	{
		echo 'refund: ' . $token;
	}

	/**
	* To list previous refunds.
	*/
	public function get_refund ()
	{
		;
	}

	/**
	* To process a transaction and make payments.
	*/
	public function post_process ( $token = NULL )
	{
		echo 'process: ' . $token;
	}

	/**
	* To list only successful transactions.
	*/
	public function get_process ()
	{
		;
	}
}