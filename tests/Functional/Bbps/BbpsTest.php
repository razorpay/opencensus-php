<?php

namespace RZP\Tests\Functional\Bbps;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class BbpsTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/BbpsTestData.php';

        parent::setUp();
    }

    public function testFetchBbpsIframeUrl()
    {
        $this->fixtures->merchant->addFeatures(['feature_bbps']);

        $this->ba->proxyAuth();

        $this->startTest();
    }
}
