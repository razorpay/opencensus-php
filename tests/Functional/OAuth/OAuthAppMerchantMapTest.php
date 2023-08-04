<?php

namespace RZP\Tests\Functional\OAuth;

use DB;
use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Merchant\Consent\Details\Repository as MerchantConsentDetailsRepo;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;

class OAuthAppMerchantMapTest extends OAuthTestCase
{
    use OAuthTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use CreateLegalDocumentsTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/OAuthAppMerchantMapTestData.php';

        parent::setUp();

        $this->ba->authServiceAuth();
    }

    public function testOAuthAppMerchantMap()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $testDataToReplace = [
                'request'  => [
                    'content' => [
                        'application_id' => $application->getId(),
                    ]
                ],
                'response' => [
                    'content'     => [
                        'entity_id'   => $application->getId(),
                    ],
                ],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals($application->getId(), $liveMapping['entity_id']);

        $this->assertEquals($application->getId(), $testMapping['entity_id']);
    }

    public function testCreateLegalDocsConsentForOAuthAuthorize()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->fixtures->create('merchant_detail:sane',
            [
                'merchant_id'       => '10000000000000',
            ]);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $this->mockBvsService();

        $testData = $this->testData['testOAuthAppMerchantMap'];

        $testData['request']['content']['application_id'] = $application->getId();
        $testData['request']['content']['env']            = "prod";
        $testData['request']['content']['ip']             = "120.121.35";
        $testData['request']['content']['scope_policies'] = [
            "App Policies"       => "https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/",
            "RazorpayX Policies" => "https://razorpay.com/terms/razorpayx/partnership/"
        ];
        $testData['response']['content']['entity_id']     = $application->getId();

        $this->runRequestResponseFlow($testData);

        $merchantConsent1 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_App Policies_Terms & Conditions']);
        $termsDetail1 = (new MerchantConsentDetailsRepo())->getById($merchantConsent1->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent1->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent1->getStatus());
        $expectedTerms = 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/';
        $this->assertEquals($expectedTerms, $termsDetail1->getURL());

        $merchantConsent2 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_RazorpayX Policies_Terms & Conditions']);
        $termsDetail2 = (new MerchantConsentDetailsRepo())->getById($merchantConsent2->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent2->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent2->getStatus());
        $expectedTerms = 'https://razorpay.com/terms/razorpayx/partnership/';
        $this->assertEquals($expectedTerms, $termsDetail2->getURL());
    }

    public function testCreateLegalDocsConsentForOAuthAuthorizeWithCustomPolicy()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->fixtures->create('merchant_detail:sane', ['merchant_id'       => '10000000000000']);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $this->mockBvsService();

        $testData = $this->testData['testOAuthAppMerchantMap'];

        $testData['request']['content']['application_id'] = $application->getId();
        $testData['request']['content']['env']            = "prod";
        $testData['request']['content']['ip']             = "120.121.35";
        $testData['request']['content']['scope_policies'] = [
            'App Policies'  => 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/',
            'Custom Policy' => 'https://www.razorpay.com/xyz/terms'
        ];

        $testData['response']['content']['entity_id']     = $application->getId();

        $this->runRequestResponseFlow($testData);

        $merchantConsent1 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_App Policies_Terms & Conditions']);
        $termsDetail1 = (new MerchantConsentDetailsRepo())->getById($merchantConsent1->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent1->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent1->getStatus());
        $expectedTerms = 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/';
        $this->assertEquals($expectedTerms, $termsDetail1->getURL());

        $merchantConsent2 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_Custom Policy_Terms & Conditions']);
        $termsDetail2 = (new MerchantConsentDetailsRepo())->getById($merchantConsent2->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent2->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent2->getStatus());
        $expectedTerms = 'https://www.razorpay.com/xyz/terms';
        $this->assertEquals($expectedTerms, $termsDetail2->getURL());
    }

    protected function mockBvsService()
    {
        $mock = $this->mockCreateLegalDocument();

        $mock->expects($this->once())->method('createLegalDocument')->withAnyParameters();
    }

    public function testOAuthAppMerchantMapIncorrectEntityId()
    {
        $this->startTest();

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals(null, $liveMapping);

        $this->assertEquals(null, $testMapping);
    }

    public function testOAuthAppMerchantMapDuplicate()
    {
        $this->fixtures->create('merchant_access_map');

        $this->startTest();

        $liveMappings = $this->getMappings('live')['items'];

        $testMappings = $this->getMappings('test')['items'];

        $this->assertEquals(1, count($liveMappings));

        $this->assertEquals(1, count($testMappings));
    }

    public function testOAuthAppMerchantMapDuplicateWithDeleted()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->fixtures->create('merchant_access_map', ['entity_id' => $application->getId(), 'deleted_at' => Carbon::now()->getTimestamp()]);

        $testDataToReplace = [
            'request'  => [
                'content' => [
                    'application_id' => $application->getId(),
                ]
            ],
            'response' => [
                'content'     => [
                    'entity_id'   => $application->getId(),
                ],
            ],
        ];
        $this->startTest($testDataToReplace);

        $liveMappings = $this->getMappings('live')['items'];

        $testMappings = $this->getMappings('test')['items'];

        $this->assertEquals(1, count($liveMappings));

        $this->assertEquals(1, count($testMappings));
    }

    public function testOAuthAppDeleteMerchantMap()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->fixtures->create('merchant_access_map', ['id' => 'BWkmyutEXIuvvX', 'entity_id' => $application->getId()]);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $testDataToReplace = [
            'request'  => [
                'url'     => '/merchants/10000000000000/applications/' . $application->getId(),
            ],
            'response' => [
            ],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals(null, $liveMapping);

        $this->assertEquals(null, $testMapping);
    }

    public function testOAuthAppDeleteWebhook()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->fixtures->create('merchant_access_map', ['id' => 'BWkmyutEXIuvvX', 'entity_id' => $application->getId()]);

        $testDataToReplace = [
            'request'  => [
                'url'     => '/merchants/10000000000000/applications/' . $application->getId(),
            ],
            'response' => [
            ],
        ];

        $this->expectWebhookEvent('account.app.authorization_revoked');

        $this->startTest($testDataToReplace);
    }

    public function testOAuthAppDeleteMerchantMapNoEntries()
    {
        $this->startTest();

        $liveMapping = $this->getMapping('live');

        $testMapping = $this->getMapping('test');

        $this->assertEquals(null, $liveMapping);

        $this->assertEquals(null, $testMapping);
    }

    public function testOAuthSyncMerchantMap()
    {
        $this->ba->adminAuth();

        $application = $this->createOAuthApplication();

        $clients = $application->clients->all();

        $this->generateOAuthAccessTokenForClient([], $clients[0]);

        $this->generateOAuthAccessTokenForClient([], $clients[1]);

        $this->generateOAuthAccessToken();

        $this->startTest();
    }

    public function testGetConnectedApplications()
    {
        $this->fixtures->create('merchant_access_map', ['id' => 'BWkmyutEXIuvvX']);

        $this->ba->storkAppAuth();
        $this->startTest();

        $testData = $this->testData['testGetConnectedApplicationsWithServiceOwner'];

        $this->runRequestResponseFlow($testData);

        $application = $this->createOAuthApplication();

        $clients = $application->clients->all();

        $this->generateOAuthAccessTokenForClient([], $clients[0]);

        $this->generateOAuthAccessTokenForClient([], $clients[1]);

        $this->fixtures->create('merchant_access_map', ['id' => 'BWkmyutEXIuvvY', 'entity_id' => $application->getId()]);

        $testData = $this->testData['testGetConnectedApplicationsWithServiceOwnerAsApi'];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testGetConnectedApplicationsWithServiceOwnerAsRx'];

        $this->runRequestResponseFlow($testData);
    }

    protected function getMapping(string $mode)
    {
        return $this->getLastEntity(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                true,
                $mode);

    }

    protected function getMappings(string $mode)
    {
        return $this->getEntities(
                Constants\Entity::MERCHANT_ACCESS_MAP,
                [],
                true,
                $mode);

    }
}
