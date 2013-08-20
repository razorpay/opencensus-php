<?php

class ErrObj
{
	protected $name;

	protected $msg;

	protected $http_code;

	protected $params;

	public function __construct($name, $msg, $http_code, $params = null)
	{
		$this->name = $name;

		$this->msg = $msg;

		$this->http_code = $http_code;

		$this->params = $params;
	}

	public function get_details()
	{
		return array(
			'name' 		=>	$this->name, 
			'msg'		=>	$this->msg, 
			'http_code'	=>	$this->http_code,
			'param'		=>	$this->params
			);
	}
}
