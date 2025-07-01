<?php

namespace RZP\Tests\Unit\Signature;

use PHPUnit\Framework\TestCase;
use RZP\Exception\LogicException;
use RZP\Signature\PfxSignature;

class PfxSignatureTest extends TestCase
{
    private $pfxFilePath = __DIR__ . '/ps_team_dummy_cert.pfx';
    private $pfxPassword = 'testpassword';

    public function testSignGenerateSignature()
    {
        if (!file_exists($this->pfxFilePath)) {
            $this->markTestSkipped('PFX certificate file not found.');
            return;
        }

        $pfxContent = file_get_contents($this->pfxFilePath);
        $certs = [];

        if (!openssl_pkcs12_read($pfxContent, $certs, $this->pfxPassword)) {
            $this->markTestSkipped('Failed to read PFX file or invalid password or certificate expired');
        }

        $pfxSignature = new PfxSignature([
            PfxSignature::PUBLIC_KEY  => $certs['cert'],
            PfxSignature::PRIVATE_KEY => $certs['pkey'],
        ]);

        $dataToSign = "Test data for signing";

        $signature = $pfxSignature->sign($dataToSign);

        $this->assertNotEmpty(actual: $signature, message: 'Generated signature should not be empty.');
    }
}
