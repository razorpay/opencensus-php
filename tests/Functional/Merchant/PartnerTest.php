<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Tests\Functional\TestCase;

class PartnerTest extends TestCase
{
    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PartnerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->privateAuth();
    }

    public function testMarkMerchantAsPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}
