<?php

namespace RZP\Gateway\Netbanking\Rbl;

class Status
{
	const SUCCESS = "SUC";
	const FAILURE = "FAL";

	public static function getAuthSuccessStatus()
    {
        return self::SUCCESS;
    }
}
