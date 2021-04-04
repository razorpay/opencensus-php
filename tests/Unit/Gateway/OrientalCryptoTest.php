<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use phpseclib\Crypt\AES;
use RZP\Gateway\Netbanking\Obc\AESCrypto;

class OrientalCryptoTest extends TestCase
{
    // These values are arrived at using the java source code
    // @see https://drive.google.com/drive/u/0/folders/1A5ULegmYTyv3yVgAD33wwi6wQZk50Nmt

    const PLAINTEXT  = 'ABC';
    const KEY = 'ABC';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crypto = new AESCrypto(AES::MODE_ECB, self::KEY);
    }

    public function testEncryptionIsSameAsJava()
    {
        $cipher = $this->crypto->encryptString(self::PLAINTEXT);

        $this->assertEquals('YES1UWs31zkngk8aYAzRMg==', $cipher);
    }
}
