<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\FirstData;
use RZP\Constants\Mode;

class FirstDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FirstData\Gateway;
    }

    public function testSetCurlSslOpts()
    {
        // This test requires CERTIFICATE_DIR_PATH to be set to some dir where
        // you have write permissions, as a new file will be added there. Using
        // 'storage/certs' works fine. Add a 'first_data' subfolder too, and a
        // file with the value of FIRST_DATA_SERVER_CERTIFICATE as its name.

        $curl = curl_init();

        $this->gateway->setMode(Mode::TEST);

        $certDir = $this->gateway->getGatewayCertDirPath();

        $certName = $this->gateway->getClientCertificateName();

        $certPath = $certDir . '/' . $certName;

        if (file_exists($certPath) === true)
        {
            // Deleting the certificate file if it exists,
            // to check cert file generation logic.
            unlink($certPath);
        }

        // There's actually no way of checking if the
        // curl instance has had its options correctly
        // set. Curl is stupid. But calling the callback
        // function will at least confirm that the setting
        // logic does not throw an error.
        $this->gateway->setCurlSslOpts($curl);

        // Best we can do
        $this->assertFileExists($certPath);
    }
}
