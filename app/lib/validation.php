<?php

Validator::extend('alpha_space', function($attribute, $value, $parameters)
{
	return preg_match('/^[\pL ]+$/u', $value);
});

Validator::extend('address', function($attribute, $value, $parameters)
{
	return preg_match('/^[\pLa-zA-Z0-9 ,-]+$/u', $value);
});

Validator::extend('luhn', function($attribute, $value, $parameters)
{
	$number = $value;
	
	$sumTable = array(
		array(0,1,2,3,4,5,6,7,8,9),
		array(0,2,4,6,8,1,3,5,7,9));

	$sum = 0;
	$flip = 0;
	$len = strlen($number);

	for ($i = $len - 1; $i >= 0; $i--) 
	{
		$sum += $sumTable[$flip++ & 0x1][$number[$i]];
	}

	return (($sum % 10) === 0);
});

Validator::extend('month', function($attribute, $value, $parameters)
{
	$month = $value;

	if ((is_numeric($month) === false) or
		(strlen($month) > 2))
	{
		return false;
	}

	$month = intval($month);

	if (($month > 12) or
		($month < 1))
	{
		return false;
	}

	return true;
});