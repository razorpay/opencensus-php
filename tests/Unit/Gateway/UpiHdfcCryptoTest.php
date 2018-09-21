<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\Upi\Mindgate\Gateway;

class UpiMindgateCryptoTest extends TestCase
{
    const CIPHERTEXT = 'BDB5D6B2AA68B4F91AAD6DB2BDD4713B';
    const PLAINTEXT  = 'HELLO WORLD';
    public function setUp()
    {
        parent::setUp();
        $this->gateway = new Gateway;
    }

    public function testEncryption()
    {
        $cipher = $this->gateway->encrypt(self::PLAINTEXT);

        $this->assertEquals(self::CIPHERTEXT, $cipher);
    }

    public function testDecryption()
    {
        $plaintext = $this->gateway->decrypt(self::CIPHERTEXT);

        $this->assertEquals(self::PLAINTEXT, $plaintext);
    }
}
