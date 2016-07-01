<?php

namespace Gateway\Cybersource;

use Gateway\Cybersource;

final class Result
{
    /**
     * Result codes received in response for card enrollment
     */

    const ENROLLED      = 475;

    const NOT_ENROLLED  = 100;

    const SUCCESS       = 100;

    public $error_codes = array (
    	'100' => 'Success',
    	'101' => 'Request is missing one or more required fields',
    	'102' => 'One or more fields contains invalid data',
    	'150' => 'System failure. Wait a few minutes and resend the request',
    	'151' => 'Server time out',
    	'152' => 'Server time out',
    	'234' => 'Problem with our merchant configuration',
    	'475' => 'Enrolled. Authenticate before continuing the transaction',
    	'476' => 'Cannot be authenticated',
    );

}