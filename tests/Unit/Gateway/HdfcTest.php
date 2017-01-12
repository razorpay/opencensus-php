<?php

namespace RZP\Tests\Unit\Gateway;

use ReflectionClass;
use RZP\Tests\TestCase;
use RZP\Gateway\Hdfc\ErrorCode;

class HdfcTest extends TestCase
{
    /**
     * Verifies that we have error mappings and messages
     * for all error codes
     */
    public function testErrorCodes()
    {
        $oClass = new ReflectionClass ('RZP\Gateway\Hdfc\ErrorCode');
        $errorCodes = $oClass->getConstants();

        foreach ($errorCodes as $code)
        {
            $this->assertTrue(isset(ErrorCode::$errorMap[$code]), "$code missing in ErrorCode::errorMap");
            $this->assertTrue(isset(ErrorCode::$errorMessages[$code]), "$code missing in ErrorCode::errorMessages");
        }
    }
}
