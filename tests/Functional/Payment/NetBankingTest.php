<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;

/**
 * Tests that support payments (capture/refund) are working fine.
 * creates a hold payment using card 13 and then attempts to capture it followed by refund it
 * Is successful if captured successfully folowed by successful refund.
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class NetbankingTest extends TestCase
{
    public function testDummy()
    {
        ;
    }
}
