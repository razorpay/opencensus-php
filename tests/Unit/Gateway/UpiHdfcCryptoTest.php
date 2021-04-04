<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\Upi\Mindgate\Gateway;

class UpiMindgateCryptoTest extends TestCase
{
    const CIPHERTEXT = '8F3574160C3FD2AE704E5A9412FCB387';
    const PLAINTEXT  = 'HELLO WORLD';
    protected function setUp(): void
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
