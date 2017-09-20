<?php

namespace RZP\Tests\Unit\Encryption;

use RZP\Tests\TestCase;
use RZP\Encryption\PGPEncryption;

class EncryptionTest extends TestCase
{
    public function testPgpEncryptionDecryption()
    {
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
}
