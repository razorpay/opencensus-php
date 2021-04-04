<?php

namespace RZP\Tests\Unit\Gateway;

use phpseclib\Crypt\AES;

use RZP\Tests\TestCase;
use RZP\Gateway\Wallet\Sbibuddy\AESCrypto;

class SbibuddyEncryptorTest extends TestCase
{
    const SECRET = 'AESSECRETKEY';

    const ENCRYPTED_STRING = 'cE0iV1fyavqvqPkmRPnXLmcHDoo00Nr6KsutArdh+7jcISAicGJk53MvT0S9LWuC';

    public static $data = [
        'orderId'               => '8PKYvHU5BtGmcA',
        'amount'                => '500.00'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryptor = new AESCrypto(AES::MODE_ECB, self::SECRET);
    }

    public function testEncryption()
    {
        $encodedData = http_build_query(self::$data);

        $encryptedData = $this->encryptor->encryptString($encodedData);

        $this->assertEquals(self::ENCRYPTED_STRING, $encryptedData);
    }

    public function testDecryption()
    {
        $decryptedData = $this->encryptor->decryptString(self::ENCRYPTED_STRING);

        $output = [];

        parse_str($decryptedData, $output);

        $this->assertEquals(self::$data, $output);
    }
}
