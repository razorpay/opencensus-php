<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Request;
use RZP\Models\Settings\Accessor;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PartnerTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerTestData.php';

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

    public function testMarkingMerchantAsPartnerMissingType()
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

    public function testMarkingMerchantAsPartnerInvalidNameToType()
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
        $merchantId = '10000000000000';

        $merchantRequest = $this->createMerchantRequest('activation', true);

        $merchantRequestId = $merchantRequest->getPublicId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertTrue($merchant->isPartner());

        $this->assertEquals($merchant->getPartnerType(), 'reseller');
    }

    public function testMarkAsPartnerWithMissingSubmission()
    {
        $merchantRequest = $this->createMerchantRequest('activation', false);

        $merchantRequestId = $merchantRequest->getPublicId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);
    }

    public function testApprovingUnmarkAsPartnerMerchantRequest()
    {
        $merchantId = '10000000000000';

        $merchantRequest = $this->createMerchantRequest('deactivation', true);

        $merchantRequestId = $merchantRequest->getPublicId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }

    protected function createMerchantRequest(string $merchantRequestName, bool $createSubmission)
    {
        $merchantId = '10000000000000';

        $merchantRequest = $this->fixtures->create(
            'merchant_request:default_merchant_request',
            [
                Request\Entity::MERCHANT_ID => $merchantId,
                Request\Entity::TYPE        => 'partner',
                Request\Entity::NAME        => $merchantRequestName,
            ]);

        $data = [
            'partner_type' => 'reseller',
        ];

        if ($createSubmission === true)
        {
            Accessor::for ($merchantRequest, 'partner')->upsert($data)->save();
        }

        return $merchantRequest;
    }
}
