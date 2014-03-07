<?php

namespace Models\Service;

class Service
{
	public static function getNewInstance()
	{
		return new static;
	}
}