<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use phpseclib\Crypt\AES;
use RZP\Gateway\Netbanking\Oriental\Crypto;

class OrientalCryptoTest extends TestCase
{
    // These values are arrived at using the java source code
    // @see https://drive.google.com/drive/u/0/folders/1A5ULegmYTyv3yVgAD33wwi6wQZk50Nmt

    const PLAINTEXT  = "ABC";
    const KEY = 'ABC';

    public function setUp()
    {
        parent::setUp();
        $this->crypto = new Crypto(AES::MODE_ECB, self::KEY);
    }

    public function testEncryptionIsSameAsJava()
    {
        $cipher = $this->crypto->encryptString(self::PLAINTEXT);

        $this->assertEquals('YES1UWs31zkngk8aYAzRMg==', $cipher);
    }
}
