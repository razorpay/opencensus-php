<?php

class Transaction_Controller extends Base_Controller 
{

	public $restful = true;
	private $data = array();
	private $error = array();

	/**
	* To retrieve transaction details by `id`
	* Lists previous transactions if `id` not provided
	*
	* @param id (optional)
	*
	*/
	public function get_index ( $id = NULL )
	{
		echo 'transaction: ' . $id;
	}

	/**
	* To create a new transaction. Retrieve transaction token.
	*/
	public function post_index ()
	{
		// TODO: Implement Basic-Auth check.
		// tutorial: http://php.net/manual/en/features.http-auth.php;
		
	}

	/**
	* To refund a transaction.
	*/
	public function post_refund ( $id = NULL )
	{
		echo 'refund: ' . $id;
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
	public function post_process ( $id = NULL )
	{
		echo 'process: ' . $id;
	}

	/**
	* To list only successful transactions.
	*/
	public function get_process ()
	{
		;
	}
}