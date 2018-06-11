<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Models\Merchant;
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
        $this->ba->adminProxyAuth();

        $merchant = Merchant\Entity::find('10000000000000');

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->startTest();
    }

    public function testUnmarkingMerchantAsPartner()
    {
        $this->ba->adminProxyAuth();

        $merchant = Merchant\Entity::find('10000000000000');

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->startTest();
    }

    public function testMarkingMerchantAsPartnerInvalidType()
    {
        $this->ba->adminProxyAuth();

        $merchant = Merchant\Entity::find('10000000000000');

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->startTest();
    }

    public function testMerchantMarksSelfAsPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testMerchantUnmarksSelfAsPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testApprovingMarkAsPartnerMerchantRequest()
    {
        $merchantRequest = $this->fixtures->create(
                                'merchant_request:default_merchant_request',
                                [
                                    Request\Entity::TYPE => 'partner_activation',
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

    public function testApprovingUnmarkAsPartnerMerchantRequest()
    {
        $merchantId = '10000000000000';

        $merchantRequest = $this->fixtures->create(
                                'merchant_request:default_merchant_request',
                                [
                                    Request\Entity::MERCHANT_ID => $merchantId,
                                    Request\Entity::TYPE        => 'partner_deactivation',
                                    Request\Entity::NAME        => 'reseller',
                                ]);

        $this->fixtures->merchant->edit($merchantId, ['partner_type' => 'reseller']);

        $merchantRequestId = $merchantRequest->getPublicId();

        $merchantId = $merchantRequest->getMerchantId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }
}
