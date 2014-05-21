<?php

namespace Models\Service;

class Service
{
	public function __construct()
	{
		;
	}

    public static function getNewInstance()
    {
        return new static;
    }
}