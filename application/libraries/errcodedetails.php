<?php

class ErrCodeDetails
{
	protected static $x1;
	protected static $x2;
	protected static $x3;
	protected static $x4;
	protected static $x10;
	protected static $x11;
	protected static $x12;
	protected static $x13;
	protected static $x14;
	protected static $x101;
	protected static $x102;
	protected static $x103;
	protected static $x104;
	protected static $x201;
	protected static $x202;
	protected static $x203;
	protected static $x204;

	private static $init = 0;

	public function __construct()
	{
		if (self::$init === 1)
			return;

		self::$init = 1;

		self::$x1 = new ErrObj(
			'INVALID_PARAMETERS',
			'The parameters provided are invalid',
			500);

		self::$x2 = new ErrObj(
			'INVALID_KEYS',
			'Invalid keys provided',
			500);

		self::$x3 = new ErrObj(
			'INVALID_CURRENCY',
			'Currency is not supported',
			500);

		self::$x10 = new ErrObj(
			'TOKEN_ALREADY_USED',
			'Token has been used',
			500);

		self::$x11 = new ErrObj(
			'CVV_ALREADY_VERIFIED',
			'CVV already verified for the card',
			500);

		self::$x101 = new ErrObj(
			'DB_PROBLEM',
			'There is a problem with the database',
			500);

		self::$x102 = new ErrObj(
			'INTERNAL_SERVER_ERROR',
			'There is a problem with the server',
			500);
	}

	public function get_details($err_code)
	{
		$hex_err_code = 'x' . dechex((int)$err_code);
		//return self::$hex_err_code;
		if (isset(self::$$hex_err_code))
		{
			$ret = self::$$hex_err_code->get_details();
			return $ret;
		}
		else throw new InvalidArgumentException($hex_err_code . ' is invalid error code');

	}


}
