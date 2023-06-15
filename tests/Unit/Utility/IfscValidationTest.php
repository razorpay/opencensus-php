<?php

namespace RZP\Tests\Unit\Utility;

use RZP\Tests\TestCase;
use Razorpay\IFSC\IFSC;
use RZP\Models\BankAccount\OldNewIfscMapping;

class IfscValidationTest extends TestCase
{
    public function testValidationOnMappedIfsc()
    {
        $result = [
            'total' => count(OldNewIfscMapping::$oldToNewIfscMapping),
            'valid' => 0,
        ];

        foreach (OldNewIfscMapping::$oldToNewIfscMapping as $old => $mapped)
        {
            $mapped = OldNewIfscMapping::getNewIfsc($old);

            if (IFSC::validate($mapped) === true)
            {
                $result['valid']++;
            }
        }

        $this->assertEquals(21633, $result['total']);
        $this->assertEquals(20773, $result['valid']);
    }

    /**
     * This test asserts that multiple lookups in the mappings array are not required when fetching a valid IFSC for an
     * old IFSC that is currently invalid. If IFSC1 => IFSC2 and IFSC2 => IFSC3, then we need to replace the mapping
     * IFSC1 => IFSC3 to IFSC1 => IFSC3, which will ensure that a single lookup will fetch the correct IFSC.
     * Please contact @payouts-experience-oncall in slack if you are facing issues and DONT skip this test.
     */
    public function testLookupCountForFetchingUpdatedIfsc()
    {
        $oldIfscList = OldNewIfscMapping::$oldToNewIfscMapping;

        foreach ($oldIfscList as $oldIfsc => $newIfsc)
        {
            self::assertFalse(array_key_exists($newIfsc, $oldIfscList));
        }
    }
}
