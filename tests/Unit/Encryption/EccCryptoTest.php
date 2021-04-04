<?php

namespace RZP\Tests\Unit\Encryption;

use RZP\Exception\RuntimeException;
use RZP\Tests\TestCase;
use RZP\Encryption\EccCrypto;

class EccCryptoTest extends TestCase
{
    protected $pair;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EccCryptoData.php';

        parent::setUp();

        $this->pair = 'default_config_set';
    }

    public function testSigning()
    {
        $content = 'Hi! I am going to be signed.';

        $sign = $this->crypto()->sign2hex($content);

        $this->assertTrue(ctype_xdigit($sign));

        $sign = $this->crypto()->sign2Base64($content);

        $this->assertTrue((bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $sign));
    }

    public function testVerifyingBase64()
    {
        $content = 'Hi! I am going to be verified.';

        $sign = $this->crypto()->sign2Base64($content);

        $verify = $this->crypto()->verifyBase64($content, $sign);

        $this->assertTrue($verify);

        $verify = $this->crypto()->verifyBase64('I was not signed.', $sign);

        $this->assertFalse($verify);

        $content2 = 'Hi! I am going to be verified. Period.';

        $sign2 = $this->crypto()->sign2Base64($content2);

        $verify = $this->crypto()->verifyBase64($content, $sign2);

        $this->assertFalse($verify);

        $this->expectException(RuntimeException::class);

        $this->crypto()->verifyBase64($content, 'I am not even base64.');
    }

    public function testVerifyingHex()
    {
        $content = 'Hi! I am going to be verified.';

        $sign = $this->crypto()->sign2Hex($content);

        $verify = $this->crypto()->verifyHex($content, $sign);

        $this->assertTrue($verify);

        $verify = $this->crypto()->verifyHex('I was not signed.', $sign);

        $this->assertFalse($verify);

        $content2 = 'Hi! I am going to be verified. Period.';

        $sign2 = $this->crypto()->sign2Hex($content2);

        $verify = $this->crypto()->verifyHex($content, $sign2);

        $this->assertFalse($verify);

        $this->expectException(RuntimeException::class);

        $this->crypto()->verifyHex($content, 'I am not even Hex.');
    }

    protected function crypto()
    {
        return (new EccCrypto($this->testData[$this->pair]));
    }
}
