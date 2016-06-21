<?php

namespace Tests\Unit\Gateway;

use Tests\TestCase;
use Gateway\UPI\ICICI;

class UPIICICITest extends TestCase
{
    /**
     * Sample Checksum generation code is at
     * https://gist.github.com/captn3m0/6ff46f101c9afe32b696af982e7744bf
     */
    public function testChecksum()
    {
        $input = 'Hello World';
        $expectedChecksum = 'b10a8db164e0754105b7a99be72e3fe5';
        $checksum = ICICI\Gateway::generateChecksum($input);

        $this->assertEquals($expectedChecksum, $checksum);
    }
}
