<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\Upi\Idfc\Crypto;

class IdfcCryptoTest extends TestCase
{
    const DECRYPTED_DEK = '284174634921775587013963';
    const KEK           = '111111111111111111111111';
    const ENCRYPTED_DEK = 'NFUlTjmHcSmlp+IsLFIRxo2/V214rhYE1sePk5LcroY=';

    public function setUp()
    {
        $config = [
            'test_kek'          =>  '111111111111111111111111',
            'test_password'     =>  '99999'
        ];

        $this->c = new Crypto($config, 'test');
    }

    public function testEncrypt1()
    {
        $dek = $this->c->encrypt('284174634921775587013963', self::KEK);
        $this->assertEquals('NFUlTjmHcSmlp+IsLFIRxo2/V214rhYE1sePk5LcroY=', $dek);
    }

    public function testDecrypt()
    {
        $decryptedDEK = $this->c->decryptUsingKEK(self::ENCRYPTED_DEK);

        $this->assertEquals(self::DECRYPTED_DEK, $decryptedDEK);
    }

    public function testEncrypt()
    {
        $expected = '4ZX2iRqogMx7lzPvYo/cDQnJejmEpD7HlnD/0nEkROM=';
        $cipher = $this->c->encrypt('e451edb9a0923e6dda1ecf9c3c3a02', self::DECRYPTED_DEK);

        $this->assertEquals($expected, $cipher);
    }

    public function testMerchantCredentials()
    {
        $expected = 'GrjRWApPrRnZVhVTi2Htcc6ykprTcHBrnak9Pa7W26pERfBcJ/npmG9L20e+dcz4';
        $txnId = 'IDF0B916B0413F54910828B870E483DC9A3';

        $creds = $this->c->generateMerchantCredential($txnId, self::DECRYPTED_DEK);

        $this->assertEquals($expected, $creds);
    }
}
