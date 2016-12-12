<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\FirstData;
use RZP\Constants\Mode;

class FirstDataTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->gateway = new FirstData\Gateway;
    }

    public function testSetCurlSslOpts()
    {
        $curl = curl_init();

        $this->gateway->setMode(Mode::TEST);

        $certDir = $this->gateway->getGatewayCertDirPath();

        $storeId = $this->gateway->getStoreId();

        $ext = $this->gateway::CERTIFICATE_FORMAT_P12;

        $certPath = $certDir . '/' . $storeId . '.' . $ext;

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
