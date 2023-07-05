<?php

namespace Unit\Models\Merchant\Acs\TraceCode;

use RZP\Trace\TraceCode;
use RZP\Tests\Functional\TestCase;

class TraceCodeTest extends TestCase
{
    const ACCOUNT_SERVICE_API_REQUEST = 'ACCOUNT_SERVICE_API_REQUEST';
    const ACCOUNT_SERVICE_API_RESPONSE = 'ACCOUNT_SERVICE_API_RESPONSE';
    const ACCOUNT_SERVICE_API_ERROR_RESPONSE = 'ACCOUNT_SERVICE_API_ERROR_RESPONSE';


    function testTraceCodeForASVSDK()
    {
        $traceCodeList = [
            self::ACCOUNT_SERVICE_API_REQUEST,
            self::ACCOUNT_SERVICE_API_RESPONSE,
            self::ACCOUNT_SERVICE_API_ERROR_RESPONSE
        ];
        foreach ($traceCodeList as $traceCode) {
            $isTracCodeExists = defined(TraceCode::class . "::" . $traceCode);
            self::assertTrue($isTracCodeExists,);
        }
    }
}
