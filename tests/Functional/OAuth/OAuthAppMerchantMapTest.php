<?php

namespace RZP\Tests\Functional\OAuth;

use DB;
use Config;
use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Partner\Config\Entity;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Traits\MocksPartnershipsService;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;
use RZP\Models\Merchant\Consent\Details\Repository as MerchantConsentDetailsRepo;

class OAuthAppMerchantMapTest extends OAuthTestCase
{
    use OAuthTrait;
    use PartnerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use CreateLegalDocumentsTrait;
    use MocksPartnershipsService;

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

    public function testOAuthAppMerchantMapWithDashboardAccessForSameSignUpSource()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);
        $subMerchant = $this->createSubMerchantForOauth(true);
        list($partner, $partnerUser) = $this->createPartnerForOauth();

        $this->fixtures->merchant->addFeatures(['pp_subm_dashboard_access'], $partner->getId());

        $this->mockPartnershipsServiceTreatment($subMerchant->getId(),  $partner->getId(),'getSubmSignupSource');

        $this->expectstorkInvalidateAffectedOwnersCacheRequest($subMerchant->getId());

        $testDataToReplace = [
            'request'  => [
                'url'     => "/merchants/{$subMerchant->getId()}/applications",
                'content' => [
                    'application_id' => $application->getId(),
                ]
            ],
            'response' => [
                'content'     => [
                    'merchant_id' => $subMerchant->getId(),
                    'entity_id'   => $application->getId(),
                ],
            ],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getMapping('live');
        $testMapping = $this->getMapping('test');

        $this->assertEquals($application->getId(), $liveMapping['entity_id']);
        $this->assertEquals($application->getId(), $testMapping['entity_id']);

        $liveUserMapping = $this->getMerchantUserMapping('live', $partnerUser->getId(), $subMerchant->getId());
        $testUserMapping = $this->getMerchantUserMapping('test', $partnerUser->getId(), $subMerchant->getId());

        $this->assertEquals('partner', $liveUserMapping['role']);
        $this->assertEquals('partner', $testUserMapping['role']);
        $this->assertEquals('primary', $liveUserMapping['product']);
        $this->assertEquals('primary', $testUserMapping['product']);
    }
    public function testOAuthAppMerchantMapWithNoDashboardAccess()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);
        $subMerchant = $this->createSubMerchantForOauth(true);
        list($partner, $partnerUser) = $this->createPartnerForOauth();

        $this->expectstorkInvalidateAffectedOwnersCacheRequest($subMerchant->getId());

        $testDataToReplace = [
            'request'  => [
                'url'     => "/merchants/{$subMerchant->getId()}/applications",
                'content' => [
                    'application_id' => $application->getId(),
                ]
            ],
            'response' => [
                'content'     => [
                    'merchant_id' => $subMerchant->getId(),
                    'entity_id'   => $application->getId(),
                ],
            ],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getMapping('live');
        $testMapping = $this->getMapping('test');

        $this->assertEquals($application->getId(), $liveMapping['entity_id']);
        $this->assertEquals($application->getId(), $testMapping['entity_id']);

        $liveUserMapping = $this->getMerchantUserMapping('live', $partnerUser->getId(), $subMerchant->getId());
        $testUserMapping = $this->getMerchantUserMapping('test', $partnerUser->getId(), $subMerchant->getId());

        $this->assertNull($liveUserMapping);
        $this->assertNull($testUserMapping);
    }

    public function testOAuthAppMerchantMapDashboardAccessForDifferentSignUpSource()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);
        $subMerchant = $this->createSubMerchantForOauth(true);
        list($partner, $partnerUser) = $this->createPartnerForOauth();

        $this->expectstorkInvalidateAffectedOwnersCacheRequest($subMerchant->getId());
        $this->fixtures->merchant->addFeatures(['pp_subm_dashboard_access'], $partner->getId());
        $this->mockPartnershipsServiceTreatment($subMerchant->getId(),  'RandomId123456','getSubmSignupSource');

        $testDataToReplace = [
            'request'  => [
                'url'     => "/merchants/{$subMerchant->getId()}/applications",
                'content' => [
                    'application_id' => $application->getId(),
                ]
            ],
            'response' => [
                'content'     => [
                    'merchant_id' => $subMerchant->getId(),
                    'entity_id'   => $application->getId(),
                ],
            ],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getMapping('live');
        $testMapping = $this->getMapping('test');

        $this->assertEquals($application->getId(), $liveMapping['entity_id']);
        $this->assertEquals($application->getId(), $testMapping['entity_id']);

        $liveUserMapping = $this->getMerchantUserMapping('live', $partnerUser->getId(), $subMerchant->getId());
        $testUserMapping = $this->getMerchantUserMapping('test', $partnerUser->getId(), $subMerchant->getId());

        $this->assertNull($liveUserMapping);
        $this->assertNull($testUserMapping);
    }

    public function testOAuthAppMerchantMapWithDashboardAccessAndNoSubMPrimaryOwner()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);
        $subMerchant = $this->createSubMerchantForOauth(false);
        list($partner, $partnerUser) = $this->createPartnerForOauth();

        $this->expectstorkInvalidateAffectedOwnersCacheRequest($subMerchant->getId());

        $testDataToReplace = [
            'request'  => [
                'url'     => "/merchants/{$subMerchant->getId()}/applications",
                'content' => [
                    'application_id' => $application->getId(),
                ]
            ],
            'response' => [
                'content'     => [
                    'merchant_id' => $subMerchant->getId(),
                    'entity_id'   => $application->getId(),
                ],
            ],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getMapping('live');
        $testMapping = $this->getMapping('test');

        $this->assertEquals($application->getId(), $liveMapping['entity_id']);
        $this->assertEquals($application->getId(), $testMapping['entity_id']);

        $liveUserMapping = $this->getMerchantUserMapping('live', $partnerUser->getId(), $subMerchant->getId());
        $testUserMapping = $this->getMerchantUserMapping('test', $partnerUser->getId(), $subMerchant->getId());

        $this->assertNull($liveUserMapping);
        $this->assertNull($testUserMapping);
    }

    public function testCreateLegalDocsConsentForOAuthAuthorize()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);

        $this->fixtures->create('merchant_detail:sane',
            [
                'merchant_id'       => '10000000000000',
            ]);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        Config::set('services.bvs.mock', true);

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

        $merchantConsent1 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_App Policy_Terms & Conditions']);
        $termsDetail1 = (new MerchantConsentDetailsRepo())->getById($merchantConsent1->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent1->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent1->getStatus());
        $expectedTerms = 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/';
        $this->assertEquals($expectedTerms, $termsDetail1->getURL());

        $merchantConsent2 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_RazorpayX App Policy_Terms & Conditions']);
        $termsDetail2 = (new MerchantConsentDetailsRepo())->getById($merchantConsent2->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent2->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent2->getStatus());
        $expectedTerms = 'https://razorpay.com/terms/razorpayx/partnership/';
        $this->assertEquals($expectedTerms, $termsDetail2->getURL());
    }

    public function testCreateLegalDocsConsentForOAuthAuthorizeWithCustomPolicy()
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'pure_platform']);

        $this->fixtures->create('merchant_detail:sane', ['merchant_id'       => '10000000000000']);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $partnerMeteData = [
            'brand_color'   => '0000FF',
            'text_color'    => '000FFF',
            'brand_name'    => 'google',
            'policy_url'    => 'https://www.razorpay.com/xyz/terms',
            'policy_template_id'    => '1hDYlICobzOCZt'
        ];

        $this->createConfigForPartnerApp($app->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData]);

        Config::set('services.bvs.mock', true);

        $testData = $this->testData['testOAuthAppMerchantMap'];

        $testData['request']['content']['application_id'] = $app->getId();
        $testData['request']['content']['env']            = "prod";
        $testData['request']['content']['ip']             = "120.121.35";
        $testData['request']['content']['scope_policies'] = [
            'App Policies'  => 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/',
            'Custom Policy' => 'https://www.razorpay.com/xyz/terms'
        ];

        $testData['response']['content']['entity_id']     = $app->getId();

        $this->runRequestResponseFlow($testData);

        $merchantConsent1 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_App Policy_Terms & Conditions']);
        $termsDetail1 = (new MerchantConsentDetailsRepo())->getById($merchantConsent1->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent1->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent1->getStatus());
        $expectedTerms = 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/';
        $this->assertEquals($expectedTerms, $termsDetail1->getURL());

        $merchantConsent2 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_Platform Partnerships Policy_Terms & Conditions']);
        $termsDetail2 = (new MerchantConsentDetailsRepo())->getById($merchantConsent2->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent2->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent2->getStatus());
        $expectedTerms = 'https://www.razorpay.com/xyz/terms';
        $this->assertEquals($expectedTerms, $termsDetail2->getURL());
    }

    public function testCreateLegalDocsConsentForOAuthAuthorizeWithCustomAndPricingPolicy()
    {
        list($partner, $app) = $this->createPartnerAndApplication(['partner_type' => 'pure_platform']);

        $this->fixtures->create('merchant_detail:sane', ['merchant_id'       => '10000000000000']);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest('10000000000000');

        $partnerMeteData = [
            'brand_color'   => '0000FF',
            'text_color'    => '000FFF',
            'brand_name'    => 'google',
            'policy_url'    => 'https://www.razorpay.com/xyz/terms',
            'policy_template_id'    => '1hDYlICobzOCZt'
        ];

        $this->createConfigForPartnerApp($app->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData]);

        $this->fixtures->pricing->createPricingPlanWithoutMethods('1hDYlICobzOCct',['bank_transfer', 'card']);

        $partnerMeteData = [
            'pricing_policy_template_id'        => '1hDYlICobzOCZt',
            'is_valid_pricing_policy_template'  => true
        ];

        $this->createConfigForPlatformPartner($partner->getId(), null, [Entity::PARTNER_METADATA => $partnerMeteData, Entity::DEFAULT_PLAN_ID => '1hDYlICobzOCct']);

        Config::set('services.bvs.mock', true);

        $testData = $this->testData['testOAuthAppMerchantMap'];

        $testData['request']['content']['application_id'] = $app->getId();
        $testData['request']['content']['partner_id']     = $partner->getId();
        $testData['request']['content']['env']            = "prod";
        $testData['request']['content']['ip']             = "120.121.35";
        $testData['request']['content']['scope_policies'] = [
            'App Policies'  => 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/',
            'Custom Policy' => 'https://www.razorpay.com/xyz/terms'
        ];

        $testData['response']['content']['entity_id'] = $app->getId();

        $this->runRequestResponseFlow($testData);

        $merchantConsent1 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_App Policy_Terms & Conditions']);
        $termsDetail1 = (new MerchantConsentDetailsRepo())->getById($merchantConsent1->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent1->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent1->getStatus());
        $expectedTerms = 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/';
        $this->assertEquals($expectedTerms, $termsDetail1->getURL());

        $merchantConsent2 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_Platform Partnerships Policy_Terms & Conditions']);
        $termsDetail2 = (new MerchantConsentDetailsRepo())->getById($merchantConsent2->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent2->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent2->getStatus());
        $expectedTerms = 'https://www.razorpay.com/xyz/terms';
        $this->assertEquals($expectedTerms, $termsDetail2->getURL());

        $merchantConsent3 = $this->getDbEntity('merchant_consents', ['consent_for' => 'Oauth_Partner Pricing Policy_Terms & Conditions']);
        $termsDetail3 = (new MerchantConsentDetailsRepo())->getById($merchantConsent3->getDetailsId());

        $this->assertEquals('10000000000000', $merchantConsent3->getMerchantId());
        $this->assertEquals('initiated', $merchantConsent3->getStatus());
        $expectedTerms = 'https://dashboard.razorpay.com/app/partner-pricing-plans?partner_id=' . $partner->getId();
        $this->assertEquals($expectedTerms, $termsDetail3->getURL());

        //Assert whether template id stored in partner config is stored in consent metadata for Pricing policy consent entry
        $this->assertContains('1hDYlICobzOCZt', $merchantConsent3->metadata);
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

    public function testOAuthAppDeleteMerchantMapWithDashboardAccess()
    {
        $application = $this->createOAuthApplication(["partner_type" => "pure_platform"]);
        $subMerchant = $this->createSubMerchantForOauth(true);
        list($partner, $partnerUser) = $this->createPartnerForOauth();
        $this->fixtures->create('merchant_access_map', [
                'id' => 'BWkmyutEXIuvvX', 'entity_id' => $application->getId(),
                'merchant_id' => $subMerchant->getId(), 'entity_owner_id' => $partner->getId()
        ]);
        $this->fixtures->create('merchant_user', [
            'merchant_id'   => $subMerchant->getId(),
            'user_id'       => $partnerUser->getId(),
            'role'          => 'partner',
            'product'       => 'primary',
        ]);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest($subMerchant->getId());

        $testDataToReplace = [
            'request'  => [
                'url'     => "/merchants/{$subMerchant->getId()}/applications/" . $application->getId(),
            ],
            'response' => [],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getMapping('live');
        $testMapping = $this->getMapping('test');

        $this->assertEquals(null, $liveMapping);
        $this->assertEquals(null, $testMapping);

        $liveUserMapping = $this->getMerchantUserMapping('live', $partnerUser->getId(), $subMerchant->getId());
        $testUserMapping = $this->getMerchantUserMapping('test', $partnerUser->getId(), $subMerchant->getId());

        $this->assertEquals(null, $liveUserMapping);
        $this->assertEquals(null, $testUserMapping);
    }

    public function testOAuthAppDeleteMerchantMapWithDashboardAccessAndMultipleApps()
    {
        $OAuthApp1 = $this->createOAuthApplication(["partner_type" => "pure_platform"]);
        $OAuthApp2 = $this->createOAuthApplication(["partner_type" => "pure_platform"]);
        $subMerchant = $this->createSubMerchantForOauth(true);
        list($partner, $partnerUser) = $this->createPartnerForOauth();
        $this->fixtures->create('merchant_access_map', [
            'id' => 'BWkmyutEXIuvvX', 'entity_id' => $OAuthApp1->getId(),
            'merchant_id' => $subMerchant->getId(), 'entity_owner_id' => $partner->getId()
        ]);
        $this->fixtures->create('merchant_access_map', [
            'id' => 'BWkmyutEXIuvBH', 'entity_id' => $OAuthApp2->getId(),
            'merchant_id' => $subMerchant->getId(), 'entity_owner_id' => $partner->getId()
        ]);
        $this->fixtures->create('merchant_user', [
            'merchant_id'   => $subMerchant->getId(),
            'user_id'       => $partnerUser->getId(),
            'role'          => 'partner',
            'product'       => 'primary',
        ]);

        $this->expectstorkInvalidateAffectedOwnersCacheRequest($subMerchant->getId());

        $testDataToReplace = [
            'request'  => [
                'url'     => "/merchants/{$subMerchant->getId()}/applications/" . $OAuthApp1->getId(),
            ],
            'response' => [],
        ];

        $this->startTest($testDataToReplace);

        $liveMapping = $this->getDbEntities('merchant_access_map', ['merchant_id' => $subMerchant->getId()], 'live');
        $testMapping = $this->getDbEntities('merchant_access_map', ['merchant_id' => $subMerchant->getId()], 'test');

        $this->assertCount(1, $liveMapping);
        $this->assertCount(1, $testMapping);
        $this->assertEquals($OAuthApp2->getId(), $liveMapping[0]['entity_id']);
        $this->assertEquals($OAuthApp2->getId(), $testMapping[0]['entity_id']);
        $this->assertEquals($partner->getId(), $liveMapping[0]['entity_owner_id']);
        $this->assertEquals($partner->getId(), $testMapping[0]['entity_owner_id']);

        $liveUserMapping = $this->getMerchantUserMapping('live', $partnerUser->getId(), $subMerchant->getId());
        $testUserMapping = $this->getMerchantUserMapping('test', $partnerUser->getId(), $subMerchant->getId());

        $this->assertEquals('partner', $liveUserMapping['role']);
        $this->assertEquals('partner', $testUserMapping['role']);
        $this->assertEquals('primary', $liveUserMapping['product']);
        $this->assertEquals('primary', $testUserMapping['product']);
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

    protected function getMerchantUserMapping(string $mode, string $userID, string $merchantID)
    {
        return $this->getDbEntity(
            Constants\Entity::MERCHANT_USER,
            ['user_id' => $userID, 'merchant_id' => $merchantID],
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
