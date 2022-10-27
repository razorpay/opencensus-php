<?php

namespace RZP\Tests\Unit\Encryption;

use RZP\Tests\TestCase;
use RZP\Encryption\PGPEncryption;
use RZP\Encryption\AESEncryption;

class EncryptionTest extends TestCase
{
    public function testPgpEncryptionDecryption()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $dataToEncrypt = 'somerandomdata';

        $publicKey = file_get_contents(__DIR__ . '/pgp_public_test_key.asc');

        $privateKey = file_get_contents(__DIR__ . '/pgp_private_test_key.asc');

        $encryptionData = [
            'public_key'  => $publicKey,
            'private_key' => $privateKey,
        ];

        $pgpEncryption = new PGPEncryption($encryptionData);

        $encryptedData = $pgpEncryption->encrypt($dataToEncrypt);

        $decryptedData = $pgpEncryption->decrypt($encryptedData);

        $this->assertEquals($dataToEncrypt, $decryptedData);
    }

    public function testPgpEncryptionDecryptionWPassphrase()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $dataToEncrypt = 'somerandomdata';

        $publicKey = file_get_contents(__DIR__ . '/pgp_public_test_key_passphrase.asc');

        $privateKey = file_get_contents(__DIR__ . '/pgp_private_test_key_passphrase.asc');

        $encryptionData = [
            'public_key'  => $publicKey,
            'private_key' => $privateKey,
            'passphrase'  => 'razorpay',
        ];

        $pgpEncryption = new PGPEncryption($encryptionData);

        $encryptedData = $pgpEncryption->encrypt($dataToEncrypt);

        $decryptedData = $pgpEncryption->decrypt($encryptedData);

        $this->assertEquals($dataToEncrypt, $decryptedData);
    }

    public function testAesEncryptionDecryption()
    {
        $dataToEncrypt = 'somerandomdata';

        // to be changed
        $encryptionData = [
            'mode'   => 2,
            'iv'     => 'aai_wee',
            'secret' => 'kissi_ko_pata_nhi_chalega',
        ];

        $pgpEncryption = new AESEncryption($encryptionData);

        $encryptedData = $pgpEncryption->encrypt($dataToEncrypt);

        $readableHexData = bin2hex($encryptedData);

        $decryptedData = $pgpEncryption->decrypt(hex2bin($readableHexData));

        $this->assertEquals($dataToEncrypt, $decryptedData);
    }
}
