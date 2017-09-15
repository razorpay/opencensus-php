<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Gateway\Base;

class ResponseCodeMap extends Base\ResponseCodeMap
{
    const SUCCESS_CODE = 'S';
    const FAILURE_CODE = 'F';

    const RESULT_SUCCESS  = 'EXECUTED';
    const RESULT_REJECTED = 'REJECTED';
    const RESULT_FAILURE  = 'FAILURE';

    protected static $codes = [];
}
