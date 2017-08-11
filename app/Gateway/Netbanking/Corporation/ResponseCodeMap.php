<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Gateway\Base;

class ResponseCodeMap extends Base\ResponseCodeMap
{
    const SUCCESS_CODE = 'S';
    const FAILURE_CODE = 'F';

    const FUND_TRANSFER = 'T';

    protected static $codes = [];
}
