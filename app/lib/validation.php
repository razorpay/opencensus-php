<?php

Validator::extend('alpha_space', function($attribute, $value, $parameters)
{
	return preg_match('/^[\pL ]+$/u', $value);
});

Validator::extend('address', function($attribute, $value, $parameters)
{
	return preg_match('/^[\pLa-zA-Z0-9 ,-]+$/u', $value);
})