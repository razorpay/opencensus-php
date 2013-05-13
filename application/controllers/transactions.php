<?php

class Transaction_Controller extends Base_Controller 
{

	public $restful = true;
	private $data = array();
	private $error = array();

	public function get_index()
	{
		;
	}

	/**
	* To create a new transaction. Retrieve transaction token.
	*/
	public function post_index()
	{
		;
	}

	/**
	* To retrieve previous transaction details.
	*/
	public function get_retrieve()
	{
		;
	}

	/**
	* To refund a transaction.
	*/
	public function post_refund()
	{
		;
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
	public function post_process()
	{
		;
	}

	/**
	* To list only successful transactions.
	*/
	public function get_success()
	{
		;
	}