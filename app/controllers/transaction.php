<?php

use Service\Transaction as TransactionService;
use Service\BasicAuth;

class Transaction_Controller extends Base_Controller 
{

	public $restful = true;

	/**
	* To retrieve transaction details by `id`
	* Lists previous transactions if `id` not provided
	*
	* @param id (optional)
	*
	*/
	public function get_index ($token = NULL)
	{
		$merchant_id = BasicAuth::MerchantId();

		$m = BasicAuth::Merchant();
		
		if (is_null($token)) 
		{
			$t = $m->transactions;
			if (empty($t))
				return Response::error('404');
			else
				return Response::eloquent($t);
		}
		else 
		{
			$t = Transaction::where('token','=', $token)->first();
		
			if (is_null($t))
				return Response::error('404');
			else
			{
				if ($t->get_merchant() === $merchant_id)
					return Response::eloquent($t);
				else
					return Response::error('401');
			}
		}

	}

	/**
	* To create a new transaction. 
	*/
	public function post_index()
	{
		$merchant_id = BasicAuth::MerchantId();

		$txn_service = new TransactionService();
		
		$input = Input::get();

		list($txn_data, $err) = $txn_service->create($input, $merchant_id);
		
		if ($err !== ERR::SUCCESS)
		{
			return ERR::handle_error();
		}

		return Response::json($txn_data);
	}

	/**
	 * To retrieve previous transactions.
	 */
	public function get_retrieve()
	{
		$txn_service = new TransactionService();

		$input = Input::get();

		list($txn_data, $err) = $txn_service->retrieve($input);

		if ($err !== ERR::SUCCESS)
		{
			return ERR::handle_error();
		}

		return Response::json($txn_data);
	}

	/**
	* To refund a transaction.
	*/
	public function post_refund($token = NULL )
	{
		echo 'refund: ' . $token;
	}

	/**
	* To list previous refunds.
	*/
	public function get_refund()
	{
		;
	}

	/**
	* To process a transaction and make payments.
	*/
	public function post_process($token = NULL )
	{
		echo 'process: ' . $token;
	}

	/**
	* To list only successful transactions.
	*/
	public function get_process()
	{
		;
	}
}