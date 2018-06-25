<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Request;
use RZP\Models\Settings\Accessor;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PartnerTest extends OAuthTestCase
{
    use OAuthTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const PARTNER               = 'partner';
    const ACTIVATION            = 'activation';
    const DEACTIVATION          = 'deactivation';
    const DEFAULT_MERCHANT_ID   = '10000000000000';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->allowAdminToAccessMerchant();

        $this->ba->privateAuth();
    }

    public function testMarkingMerchantAsPartner()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    /**
     * Tests marking a merchant as a partner after the merchant has been marked and unmarked as a partner before
     */
    public function testMarkingMerchantAsPartnerAgain()
    {
        $this->createMerchantRequest(self::ACTIVATION, true);

        // Using a different the merchant request id here
        $this->createMerchantRequest(
            self::DEACTIVATION,
            false,
            Merchant\Constants::RESELLER,
            [
                'id' => 'mrId1000000001',
            ]);

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testUnmarkingMerchantAsPartner()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testMarkingMerchantAsPartnerMissingType()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }
    public function testMarkingMerchantAsPartnerInvalidType()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testMarkingMerchantAsPartnerInvalidNameToType()
    {
        $this->ba->adminProxyAuth();

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

    public function testMarkAsPartnerWithMissingSubmission()
    {
        $merchantRequest = $this->createMerchantRequest(self::ACTIVATION, false);

        $merchantRequestId = $merchantRequest->getPublicId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);
    }

    public function testApprovingMarkAsPartnerMerchantRequest()
    {
        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::ACTIVATION, true);

        $merchant = $merchantRequest->merchant;

        // Mock create application call to auth service
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
            'name'     => $merchant->getName(),
            'website'  => $merchant->getWebsite(),
            'logo_url' => null,
            'type'     => self::PARTNER,
        ];

        $requestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail('applications', 'POST', $requestParams);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $this->assertTrue($merchant->isPartner());

        $this->assertEquals($merchant->getPartnerType(), Merchant\Constants::RESELLER);
    }

    /**
     * Test approving the partner activation merchant request for a merchant who is already a partner.
     */
    public function testMarkPartnerAsPartner()
    {
        $merchantId = '10000000000000';

        $merchantRequest = $this->createMerchantRequest('activation', true);

        $merchantRequestId = $merchantRequest->getPublicId();

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::RESELLER);

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);
    }

    public function testApprovingUnmarkAsPartnerMerchantRequest()
    {
        $merchantId = self::DEFAULT_MERCHANT_ID;

        $partnerData = $this->getDummyPartnerAttributes();

        // Create an oauth application using factory
        $this->createOAuthApplication($partnerData);

        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $this->setAuthServiceMockDetail('applications/8ckeirnw84ifke', 'PUT', $requestParams);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::RESELLER);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertTrue($merchant->isPartner());

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::DEACTIVATION);

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }

    public function testApprovingPurePlatformActivationRequest()
    {
        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(
            self::ACTIVATION,
            true,
            Merchant\Constants::PURE_PLATFORM);

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $this->assertTrue($merchant->isPartner());
    }

    public function testApprovingPurePlatformDeactivationRequest()
    {
        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->markMerchantAsPartner($merchantId, Merchant\Constants::PURE_PLATFORM);

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::DEACTIVATION);

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }

    public function testUnmarkNonPartnerMerchantAsPartner()
    {
        $merchantRequest = $this->createMerchantRequest('deactivation', true);

        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', self::DEFAULT_MERCHANT_ID, $liveMode);

        $this->assertFalse($merchant->isPartner());
    }

    public function testLinkedAccountMarkedAsPartner()
    {
        // Create a merchant request
        $merchantRequest = $this->createMerchantRequest(self::ACTIVATION, true);

        $this->fixtures->merchant->createAccount('100DemoAccount');

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['parent_id' => '100DemoAccount']);

        // Set the admin auth
        $liveMode = $this->app['basicauth']->getLiveConnection();

        $this->ba->adminAuth($liveMode);

        $testData = $this->testData[__FUNCTION__];

        $merchantRequestId = $merchantRequest->getPublicId();

        $testData['request']['url'] = '/merchant/requests/' . $merchantRequestId;

        $this->startTest($testData);
    }

    protected function createMerchantRequest(
        string $merchantRequestName,
        bool $createSubmission = false,
        string $partnerType = Merchant\Constants::RESELLER,
        array $attributes = [])
    {
        $defaults = [
            Request\Entity::MERCHANT_ID => self::DEFAULT_MERCHANT_ID,
            Request\Entity::TYPE        => self::PARTNER,
            Request\Entity::NAME        => $merchantRequestName,
        ];

        $attributes = array_merge($attributes, $defaults);

        $merchantRequest = $this->fixtures->create('merchant_request:default_merchant_request', $attributes);

        if (($merchantRequestName === self::ACTIVATION) and ($createSubmission === true))
        {
            $data = [
                'partner_type' => $partnerType,
            ];

            Accessor::for ($merchantRequest, self::PARTNER)->upsert($data)->save();
        }

        return $merchantRequest;
    }

    protected function getDummyPartnerAttributes(array $attributes = []): array
    {
        $defaults = [
            'id'          => '8ckeirnw84ifke',
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'name'        => 'Internal',
            'website'     => 'https://www.razorpay.com',
            'logo_url'    => '/logo/app_logo.png',
            'category'    => null,
            'type'        => self::PARTNER,
        ];

        $attributes = array_merge($defaults, $attributes);

        return $attributes;
    }

    protected function allowAdminToAccessMerchant()
    {
        $merchant = Merchant\Entity::find(self::DEFAULT_MERCHANT_ID);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);
    }

    protected function markMerchantAsPartner(string $merchantId, string $partnerType)
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => $partnerType]);
    }
}
