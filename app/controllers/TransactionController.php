<?php

use Service\Transaction;
use Service\BasicAuth;

class TransactionController extends BaseController 
{

	/**
	* Retrieves transaction details by `id`
	* Lists previous transactions if `id` not provided
	*
	* @param token (optional)
	*
	*/
	public function getIndex ($id = null)
	{
		$merchant_id = BasicAuth::getInstance()->MerchantId();

		$m = BasicAuth::getInstance()->Merchant();
		
		if ($id === null) 
		{
			$t = $m->transactions();

			if (empty($t))
			{
				return Response::json(array());
			}
			else
			{
				return Response::json($t);
			}
		}
		else 
		{
			$t = Transaction::where('id', '=', $id)->first();
		
			if ($t === null)
			{
				return Response::error('404');
			}
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
	* Create a new transaction. 
	*/
	public function postIndex()
	{
		$txn_service = new Transaction();
		
		$input = Input::get();

		$input['merchant_id'] = BasicAuth::getInstance()->MerchantId();

		list($txn_data, $err) = $txn_service->create($input, $merchant_id);
		
		if ($err !== ERR::SUCCESS)
		{
			return ERR::handle_error();
		}

		return Response::json($txn_data);
	}

	/**
	 * Retrieve previous transactions.
	 */
	public function getRetrieve()
	{
		$txn_service = new Transaction();

		$input = Input::get();

		list($txn_data, $err) = $txn_service->retrieve($input);

		if ($err !== ERR::SUCCESS)
		{
			return ERR::handle_error();
		}

		return Response::json($txn_data);
	}

	/**
	* Refund a transaction.
	*/
	public function postRefund($token = NULL )
	{
		echo 'refund: ' . $token;
	}

	/**
	* List previous refunds.
	*/
	public function getRefund()
	{
		;
	}

	/**
	* To process a transaction and make payments.
	*/
	public function postProcess($token = NULL )
	{
		echo 'process: ' . $token;
	}

	/**
	* To list only successful transactions.
	*/
	public function getProcess()
	{
		;
	}
}