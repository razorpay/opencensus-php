<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Models\Merchant\Request;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PartnerTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PartnerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->ba->privateAuth();
    }

    public function testMarkingMerchantAsPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMarkingMerchantAsPartnerInvalidType()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testApprovingMarkAsPartnerMerchantRequest()
    {
        $merchantRequest = $this->fixtures->create(
            'merchant_request:default_merchant_request',
            [
                Request\Entity::TYPE => 'partner',
                Request\Entity::NAME => 'reseller',
            ]);

        $merchantRequestId = $merchantRequest->getPublicId();

        $merchantId = $merchantRequest->getMerchantId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertTrue($merchant->isPartner());
    }
}
