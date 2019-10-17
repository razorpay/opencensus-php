<?php

namespace RZP\Tests\Functional\Merchant;

use Config;
use RZP\Constants\Mode;
use RZP\Services\RazorXClient;
use RZP\Services\HubspotClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Document\Type;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\Detail\Constants as MerchantDetailsConstant;

/**
 * @group dns-sensitive
 */
class ActivationTest extends TestCase
{
    use MocksDnsTrait;
    use EntityActionTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ActivationTestData.php';

        parent::setUp();

        $this->setupMockDns();

        $this->fixtures->create('org:hdfc_org');
    }

    public function testMerchantActivationCategoriesResponseForAdminAuth()
    {
        $merchant = $this->fixtures->create('merchant');

        // allow admin to access merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminProxyAuth($merchant->id);

        $this->startTest();
    }

    public function testMerchantActivationCategoriesResponseForNonAdminAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPostInstantActivationRequiredField()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testPostInstantActivation()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->mockHubSpotClient('trackL1ContactProperties');

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->getHoldFunds());

        $this->assertFalse($merchant->merchantDetail->isSubmitted());

        $this->assertEquals($merchant->getWebsite(), 'https://example.com');

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getWebsite(), 'https://example.com');
    }

    public function testInstantActivationForForUnRegisteredTORegisteredSwitch()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        Config::set('applications.mozart.mock', true);

        Config::set('applications.mozart.mock.status', MerchantDetailsConstant::SUCCESS);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'     => $merchantId,
                'business_type'   => '11',
                'activation_flow' => 'blacklist',
            ]
        );

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->mockRazorX(__FUNCTION__, 'non_registered_onboarding', 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals(ActivationFlow::WHITELIST, $merchantDetail->getActivationFLow());
        $this->assertEquals(ActivationFlow::WHITELIST, $merchantDetail->getInternationalActivationFlow());
        $this->assertEquals('7', $merchantDetail->getAttribute(Entity::TRANSACTION_VOLUME));
        $this->assertEquals('8', $merchantDetail->getAttribute(Entity::DEPARTMENT));
    }

    public function testInstantActivationForUnregisteredBusinessWithBlacklistCategories()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->mockRazorX(__FUNCTION__, 'non_registered_onboarding', 'on');

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => $merchantId,
            ]
        );

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testInstantActivationForForRegisteredTOUnRegisteredSwitch()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        Config::set('applications.mozart.mock', true);

        Config::set('applications.mozart.pan_authentication', MerchantDetailsConstant::SUCCESS);

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => $merchantId,
                'business_type' => '3',
                'activation_flow' => 'blacklist',
            ]
        );

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->mockRazorX(__FUNCTION__, 'non_registered_onboarding', 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $testData = $this->testData['testInstantActivationForUnregisteredBusiness'];

        $this->startTest($testData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNull($merchantDetail->getActivationFLow());
        $this->assertNull($merchantDetail->getInternationalActivationFlow());
    }

    public function testInstantActivationForUnregisteredBusiness()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        Config::set('applications.mozart.mock', true);

        Config::set('applications.mozart.pan_authentication', MerchantDetailsConstant::SUCCESS);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->mockRazorX(__FUNCTION__, 'non_registered_onboarding', 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNull($merchantDetail->getActivationFLow());
        $this->assertNull($merchantDetail->getInternationalActivationFlow());
    }

    public function testIAForUnregisteredBusinessFeatureEnabled()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $tests = [
            'testIAForUnregisteredBusinessFeatureEnabledNameMisMatch'     => MerchantDetailsConstant::SUCCESS,
            'testIAForUnregisteredBusinessFeatureEnabledIncorrectDetails' => MerchantDetailsConstant::INCORRECT_DETAILS,
            'testIAForUnregisteredBusinessFeatureEnabledTimeout'          => MerchantDetailsConstant::FAILURE,
            'testIAForUnregisteredBusinessFeatureEnabled'                 => MerchantDetailsConstant::SUCCESS, // at bottom because once successful, the request can not be tried again
        ];

        Config::set('applications.mozart.mock', true);

        foreach ($tests as $test => $mockStatus)
        {

            Config::set('applications.mozart.pan_authentication', $mockStatus);

            $this->mockRazorX($test,'non_registered_onboarding','on');

            $testData = $this->testData[$test];

            $this->runRequestResponseFlow($testData);
        }
    }

    protected function mockHubSpotClient($methodName)
    {
        $hubSpotMock = $this->getMockBuilder(HubspotClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods([$methodName])
                            ->getMock();

        $this->app->instance('hubspot', $hubSpotMock);

        $hubSpotMock->expects($this->exactly(1))
                    ->method($methodName);
    }

    public function mockRazorX(string $functionName, string $featureName, string $variant)
    {
        $testData                       = &$this->testData[$functionName];

        $uniqueLocalId                  = RazorXClient::getLocalUniqueId('1cXSLlUU8V9sXl', $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];
    }

    public function testInstantActivationOfSubscriptionsForActiveMerchants()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->mockRazorX(__FUNCTION__,'instant_activation_2_0_products','on');

        $this->fixtures->merchant->activate($merchantId);
        $this->ba->proxyAuth('rzp_test_' . $merchantId);
        $this->startTest();
        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals("approved", $merchant->merchantDetail->getSubscriptionsActivationStatus());

    }

    public function testInstantActivationOfSubscriptionsForInActiveMerchants()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->mockRazorX(__FUNCTION__,'instant_activation_2_0_products','on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);
        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals("pending", $merchant->merchantDetail->getSubscriptionsActivationStatus());

    }

    public function testInstantActivationOfRoutesForActiveMerchants()
    {
        $this->markTestSkipped("Skipping the test until Route is added to instant Activation bucket");

        $merchantId = '1cXSLlUU8V9sXl';
        $this->mockRazorX(__FUNCTION__,'instant_activation_2_0_products','on');

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);



        $this->fixtures->merchant->activate($merchantId);
        $this->ba->proxyAuth('rzp_test_' . $merchantId);
        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals("approved", $merchant->merchantDetail->getMarketplaceActivationStatus());

    }

    public function testInstantActivationOfRoutesForInActiveMerchants()
    {
        $this->markTestSkipped("Skipping the test until Route is added to instant Activation bucket");

        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->mockRazorX(__FUNCTION__,'instant_activation_2_0_products','on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);
        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals("pending", $merchant->merchantDetail->getMarketplaceActivationStatus());

    }

    public function testInstantActivationWithBlacklistedCategoryForEmi()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $merchantMethods = $this->getDbEntityById('merchant', $merchantId)->getMethods();

        $this->assertFalse($merchantMethods->isEmiEnabled());
    }

    public function testInstantActivationWithWhitelistedCategoryForEmi()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $merchantMethods = $this->getDbEntityById('merchant', $merchantId)->getMethods();

        $this->assertTrue($merchantMethods->isEmiEnabled());
    }

    public function testPostInstantActivationLinkedAccount()
    {
        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $merchantId = $linkedAccount->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testUpdateActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('greylist', $liveMerchant->merchantdetail->getActivationFlow());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame('greylist', $testMerchant->merchantdetail->getActivationFlow());
    }

    public function testUpdateCategoryDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('6211', $liveMerchant->getCategory());
        $this->assertSame('mutual_funds', $liveMerchant->getCategory2());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame('6211', $testMerchant->getCategory());
        $this->assertSame('mutual_funds', $testMerchant->getCategory2());
    }

    /**
     * The instant activation route should not accept the request if the merchant is already activated
     */
    public function testPostInstantActivationByActivatedMerchant()
    {
        $this->fixtures->merchant->activate(self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * for blacklist activation flow
     */
    public function testBlacklistInstantActivation()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => self::DEFAULT_MERCHANT_ID,
            'contact_email' => 'test@razorpay.com',
        ]);

        $this->ba->adminAuth();
        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGreylistInstantActivation()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => self::DEFAULT_MERCHANT_ID,
            'contact_email' => 'test@razorpay.com',
        ]);

        $this->ba->adminAuth();
        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    /**
     * Blacklist merchant should be able to resubmit L1 activation form (basic activation form)
     */
    public function testL1ResubmissionForBlacklist()
    {
        $merchantDetail = $this->fixtures->create(
            'merchant_detail',
            [MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST,]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('whitelist', $liveMerchant->merchantdetail->getActivationFlow());
    }

    public function testKycSubmissionForInstantlyActivatedMerchant()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getInstantlyActivatedMerchantDetailData($merchantId);
        // Adding the file upload attributes for simplicity of the test
        $otherMerchantDetailAttributes = [
            'address_proof_url'    => '124',
            'business_pan_url'     => '124',
            'business_proof_url'   => '124',
            'promoter_address_url' => '124',
        ];
        $data = array_merge($data, $otherMerchantDetailAttributes);
        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $this->startTest();

        $testData = $this->testData['submitKyc'];
        $this->startTest($testData);
    }

    public function testKycSubmissionWhenPoaIsOcrVerified()
    {
        $this->kycSubmissionWithSuccessCases('verified', 'verified');
    }

    public function testKycUnregisteredCanSubmitWithAadhar()
    {
        $this->validateKYCSubmission([Type::AADHAR_FRONT, Type::AADHAR_BACK]);
    }

    public function testKycUnregisteredCanSubmitWithPassport()
    {
        $this->validateKYCSubmission([Type::PASSPORT_BACK, Type::PASSPORT_FRONT]);
    }

    public function testKycUnregisteredCanSubmitWithDL()
    {
        $this->validateKYCSubmission([Type::DRIVER_LICENSE_FRONT]);
    }

    public function testKycUnregisteredCanSubmitWithVoterId()
    {
        $this->validateKYCSubmission([Type::VOTER_ID_BACK, Type::VOTER_ID_FRONT]);
    }

    private function validateKYCSubmission(array $documentTypes)
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $otherMerchantDetailAttributes = [
            'business_type' => 2,
        ];

        $data = array_merge($data, $otherMerchantDetailAttributes);

        $this->fixtures->create('merchant_detail', $data);

        $testSuit = 'validateUnregisteredKycSubmission';

        $this->mockRazorX($testSuit, 'non_registered_onboarding', 'on');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $testData = $this->testData[$testSuit];

        $response = $this->startTest($testData);

        $this->assertFalse($response['can_submit']);

        foreach ($documentTypes as $documentType)
        {
            $this->createMerchantDocumentEntries($merchantId, $documentType);
        }

        $response = $this->startTest($testData);

        $this->assertTrue($response['can_submit']);
    }

    public function testKycSubmissionWhenPoaIsOcrYetTobeVerified()
    {
        $this->kycSubmissionWithSuccessCases(null, 'verified');
    }

    public function kycSubmissionWithSuccessCases($poaVerificationStatus, $bankDetailsVerificationStatus = null)
    {
        $this->createMerchantDocumentEntries('1cXSLlUU8V9sXl', 'aadhar_front');
        $this->createMerchantDocumentEntries('1cXSLlUU8V9sXl', 'aadhar_back');

        $this->getKycVerificationForPoaVerificationSetup($poaVerificationStatus, $bankDetailsVerificationStatus);

        $testSuits = [
            'testKycSubmissionWhenPoaIsVerified',
            'submitKycActivated'
        ];

        foreach ($testSuits as $index => $testSuit)
        {
            $this->mockRazorX($testSuit, 'non_registered_onboarding', 'on');

            $testData = $this->testData[$testSuit];

            $this->startTest($testData);
        }
    }

    public function testKycSubmissionWithFailedPoaStatus()
    {
        $this->kycSubmissionWithFailureCases('failed');
    }

    public function testKycSubmissionWithPendingPoaStatus()
    {
        $this->kycSubmissionWithFailureCases('pending');
    }

    public function kycSubmissionWithFailureCases($poaVerificationStatus, $bankDetailsVerificationStatus = null)
    {
        $this->createMerchantDocumentEntries('1cXSLlUU8V9sXl', 'aadhar_front', 'failed');
        $this->createMerchantDocumentEntries('1cXSLlUU8V9sXl', 'aadhar_back', 'failed');

        $this->getKycVerificationForPoaVerificationSetup($poaVerificationStatus, $bankDetailsVerificationStatus);


        $testSuits = [
            'testKycSubmissionWhenPoaIsFailed',
            'submitKyc'
        ];

        foreach ($testSuits as $index => $testSuit)
        {
            $this->mockRazorX($testSuit, 'non_registered_onboarding', 'on');

            $testData = $this->testData[$testSuit];

            $this->startTest($testData);
        }
    }

    private function getKycVerificationForPoaVerificationSetup($poaVerificationStatus, $bankDetailsVerificationStatus = null)
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getInstantlyActivatedMerchantDetailData($merchantId);
        // Adding the file upload attributes for simplicity of the test
        $otherMerchantDetailAttributes = [
            'address_proof_url'                => '124',
            'business_pan_url'                 => '124',
            'business_proof_url'               => '124',
            'promoter_address_url'             => '124',
            'business_type'                    => 2,
            'poa_verification_status'          => $poaVerificationStatus,
            'bank_details_verification_status' => $bankDetailsVerificationStatus,
        ];
        $data = array_merge($data, $otherMerchantDetailAttributes);
        $this->fixtures->create('merchant_detail', $data);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);
    }


    public function testKYCVerificationForInstantlyActivatedMerchant()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);
        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $data = $this->getKycSubmittedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        // @todo: Lock the form once submitted. Change this to assertTrue then
        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId, 'test');
        $this->assertFalse($merchantDetail->isLocked());

        // under_review to rejected
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'rejected');
        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertFalse($merchant->isLive());
        $this->assertTrue($merchant->getHoldFunds());

        // rejected to under_review
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'under_review');
        $this->startTest($testData);

        // under_review to activated
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');
        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertTrue($merchant->isLive());
        $this->assertFalse($merchant->getHoldFunds());

        // Changing activation_status to activated should lock the form
        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId, 'test');
        $this->assertTrue($merchantDetail->isLocked());
    }

    /**
     * Asserts that the funds cannot be released if the bank account entity is not specified
     */
    public function testReleaseFundsWithoutBankAccount()
    {
        $merchantId = $this->fixtures->create('merchant')->getId();

        $data = $this->getInstantlyActivatedMerchantDetailData($merchantId);
        $this->fixtures->create('merchant_detail', $data);

        $this->ba->adminAuth();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchants/' . $merchantId . '/action';

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $this->startTest();
    }

    public function testPostInstantActivationFetaureCheck()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->fixtures->edit('merchant', '1cXSLlUU8V9sXl', ['pricing_plan_id' => '1In3Yh5Mluj605', 'international' => 0]);

        $this->fixtures->pricing->createPromotionalPlan();

        $this->fixtures->edit('pricing', '1AXp2Xd3t5aRLX', ['international' => 1]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }


    /**
     * for older merchants (merchants before instant activation) , if merchants had partially filled L2
     * (business category and business subcategory) , now fills L1 form without changing business category or business
     * subcategory then category and category 2 should set if not already set
     */
    public function testInstantActivationForOlderMerchant()
    {
        $merchantId = $this->createWhiteListMerchantFixture();

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    /**
     * creates entities require for whitelist flow L1 submission
     *
     * @return string
     */
    protected function createWhiteListMerchantFixture(): string
    {
        $plan = $this->fixtures->create('pricing');

        // merchant detail internally creates merchant entity
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            Entity::BUSINESS_CATEGORY    => 'ecommerce',
            Entity::BUSINESS_SUBCATEGORY => 'fashion_and_lifestyle',
            Entity::PROMOTER_PAN         => 'ABCDE1234E',
            Entity::BUSINESS_NAME        => 'test',
            Entity::BUSINESS_WEBSITE     => 'https://www.example.com',
            Entity::BUSINESS_TYPE        => '1',
            Entity::BUSINESS_DBA         => 'test',
        ]);

        $this->fixtures->edit('merchant', $merchantDetail->getMerchantId(), [
            \RZP\Models\Merchant\Entity::PRICING_PLAN_ID => $plan->getPlanId()
        ]);

        $this->fixtures->edit('methods', $merchantDetail->getMerchantId(), [
            Entity::MERCHANT_ID => $merchantDetail->getMerchantId(),
            'disabled_banks'    => [],
            'banks'             => '[]',
            'netbanking'        => 0,
            'debit_card'        => 0,
            'credit_card'       => 0,
        ]);

        $this->fixtures->edit('pricing', $plan->getId(), ['international' => 1]);

        return $merchantDetail->getMerchantId();
    }

    protected function changeActivationStatus(& $requestContent, & $responseContent, $newStatus)
    {
        $requestContent['activation_status'] = $newStatus;

        $responseContent['activation_status'] = $newStatus;
    }

    protected function getInstantlyActivatedMerchantData()
    {
        return [
            'activated'    => 1,
            'activated_at' => 1539542931,
            'hold_funds'   => 1,
        ];
    }

    protected function getInstantlyActivatedMerchantDetailData($merchantId)
    {
        return [
            'merchant_id'          => $merchantId,
            'business_category'    => 'ecommerce',
            'business_subcategory' => 'fashion_and_lifestyle',
            'promoter_pan'         => 'ABCDE0000Z',
            'promoter_pan_name'    => 'John Doe',
            'activation_status'    => 'instantly_activated',
            'activation_flow'      => 'whitelist',
        ];
    }

    protected function getKycSubmittedMerchantData()
    {
        return [
            'activated'    => 1,
            'activated_at' => 1539542931,
            'hold_funds'   => 1,
        ];
    }

    protected function getKycSubmittedMerchantDetailData($merchantId)
    {
        return [
            'merchant_id'                 => $merchantId,
            'business_category'           => 'ecommerce',
            'business_subcategory'        => 'fashion_and_lifestyle',
            'promoter_pan'                => 'ABCDE0000Z',
            'promoter_pan_name'           => 'John Doe',
            'activation_status'           => 'instantly_activated',
            'activation_flow'             => 'whitelist',
            'contact_name'                => 'test',
            'contact_mobile'              => '9123456789',
            'business_type'               => '1',
            'business_name'               => 'Acme',
            'business_dba'                => 'Acme',
            'bank_account_name'           => 'test',
            'bank_account_number'         => '123456789012345',
            'bank_branch_ifsc'            => 'ICIC0000001',
            'business_operation_address'  => 'Test address',
            'business_operation_state'    => 'Karnataka',
            'business_operation_city'     => 'Bengaluru',
            'business_operation_pin'      => '560030',
            'business_registered_address' => 'Test address',
            'business_registered_state'   => 'Karnataka',
            'business_registered_city'    => 'Bengaluru',
            'business_registered_pin'     => '560030',
            'submitted'                   => 1,
            'submitted_at'                => 1539543931,
        ];
    }

    public function testWhitelistInternational()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'whitelist');

        $this->assertFalse($merchant->convertOnApi());
    }

    public function testWhitelistInternationalWithNoWebsite()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->edit('merchant', $merchantId, ['website' => null]);

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'whitelist');

        $this->assertNull($merchant->convertOnApi());
    }

    public function testWhitelistInternationalWithWebsiteAlreadySet()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->edit('merchant', $merchantId, ['website' => 'https://www.example.com']);

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'whitelist');

        $this->assertFalse($merchant->convertOnApi());
    }

    public function testWhitelistInternationalWithNoWebsiteAlreadySet()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->edit('merchant', $merchantId, ['website' => null]);

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'whitelist');

        $this->assertFalse($merchant->convertOnApi());
    }

    public function testBlacklistInternational()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'blacklist');

        $this->assertNull($merchant->convertOnApi());
    }

    public function testGreylistInternational()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'greylist');

        $this->assertNull($merchant->convertOnApi());
    }

    public function testGreylistInternationalInstantlyActivated()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'greylist');

        $this->assertNull($merchant->convertOnApi());
    }

    public function testGreylistInternationalNonInstantActivation()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'greylist');

        $this->assertNull($merchant->convertOnApi());
    }

    /**
     * Validates that On L1 form submission for non rzp org(whitelist international activation flow) merchant,
     * international activation flow and international should not be set.
     */
    public function testWhitelistInternationalForNonRZPOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId,  Org::HDFC_ORG);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNull($merchantDetails->getInternationalActivationFlow());

        $this->assertNull($merchant->convertOnApi());
    }

    /**
     * Validates On L1 form submission for non rzp org(greylist international activation flow) merchant,
     * international activation flow and international should not be set.
     */
    public function testGreylistInternationalForNonRZPOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId,  Org::HDFC_ORG);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNull($merchantDetails->getInternationalActivationFlow());

        $this->assertNull($merchant->convertOnApi());
    }

    /**
     * Validates that On L2 form submission(when merchant belongs to greylist activation flow) for non rzp org(greylist international activation flow) merchant,
     * international should not be set.
     */
    public function testGreylistInternationalOnForNonRZPOrg()
    {
        $data = [
            'submitted'             => 1,
            'business_category'     => 'not_for_profit',
            'business_subcategory'  => 'educational',
            'activation_status'     => 'under_review'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->edit('merchant', $merchantId, ['international' => 0, 'org_id' => Org::HDFC_ORG]);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($activationRequest);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertFalse($merchant->isInternational());

        $this->assertNull($merchant->convertOnApi());
    }

    /**
     * Validates that On L2 form submission(when merchant belongs to whitelist activation flow) for non rzp org(greylist international activation flow) merchant,
     * international should not be set.
     */
    public function testGreylistInternationalInstantActivationForNonRZPOrg()
    {
        $data = [
            'submitted'             => 1,
            'business_category'     => 'healthcare',
            'business_subcategory'  => 'clinic',
            'activation_status'     => 'under_review'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->edit('merchant',
                              $merchantId,
                              ['activated' => 1, 'international' => 0, 'org_id' => Org::HDFC_ORG]);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($activationRequest);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertFalse($merchant->isInternational());

        $this->assertNull($merchant->convertOnApi());
    }

    protected function runFixturesForInternationalActivation(string $merchantId, string $orgId = Org::RZP_ORG)
    {
        $this->fixtures->edit('merchant', $merchantId, ['international' => 0, 'org_id' => $orgId]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => $merchantId
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);
    }

    protected function setUpRazorxMock(string $experimentVal = 'on')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
             ->willReturn($experimentVal);
    }

    public function testGreylistInternationalOnKYC()
    {
        $this->setUpRazorxMock();

        $data = [
            'submitted'             => 1,
            'business_category'     => 'not_for_profit',
            'business_subcategory'  => 'educational',
            'activation_status'     => 'under_review'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->edit('merchant', $merchantId, ['international' => 0]);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($activationRequest);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->isInternational());

        $this->assertFalse($merchant->convertOnApi());
    }

    public function testGreylistInternationalInstantActivationOnKYC()
    {
        $this->setUpRazorxMock();

        $data = [
            'submitted'             => 1,
            'business_category'     => 'healthcare',
            'business_subcategory'  => 'clinic',
            'activation_status'     => 'under_review'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->edit('merchant', $merchantId, ['activated' => 1, 'international' => 0]);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($activationRequest);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->isInternational());

        $this->assertFalse($merchant->convertOnApi());
    }

    public function testBlacklistInternationalOnKYC()
    {
        $this->setUpRazorxMock();

        $data = [
            'submitted'             => 1,
            'business_category'     => 'secutities',
            'business_subcategory'  => 'commodities',
            'activation_status'     => 'under_review'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->edit('merchant', $merchantId, ['international' => 0]);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($activationRequest);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertFalse($merchant->isInternational());

        $this->assertNull($merchant->convertOnApi());
    }

    public function testNeedsClarificationResponseForAdminAuth()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    /**
     * @param        $merchantId
     * @param        $documentType
     * @param string $ocrVerificationStatus
     */
    private function createMerchantDocumentEntries($merchantId, $documentType, $ocrVerificationStatus = 'verified'): void
    {
        $this->fixtures->create(
            'merchant_document',
            [
                'merchant_id'   => $merchantId,
                'document_type' => $documentType,
                'ocr_verify'    => $ocrVerificationStatus,
            ]);
    }
}
