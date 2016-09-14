<?php

namespace RZP\Services\Mock;

use RZP\Exception;
use Requests;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class MaxMind
{
    const LICENSE_KEY = 'license_key';

    public function __construct($app)
    {
        ;
    }

    public function query($input)
    {
        return null;
    }


}