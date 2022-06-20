<?php

namespace RZP\Tests\Unit\Utility;

use RZP\Exception;
use RZP\Tests\TestCase;
use Razorpay\IFSC\IFSC;
use RZP\Models\BankAccount;
use RZP\Models\BankAccount\OldNewIfscMapping;

class IfscValidationTest extends TestCase
{
    public function testValidationOnMappedIfsc()
    {
        $result = [
            'total'     => count(OldNewIfscMapping::$oldToNewIfscMapping),
            'valid'     => 0,
        ];

        foreach (OldNewIfscMapping::$oldToNewIfscMapping as $mapped)
        {
            if (IFSC::validate($mapped) === true)
            {
                $result['valid']++;
            }
        }

        $this->assertArraySubset([
            'total' => 21576,
            'valid' => 21398,
        ], $result);
    }
}
