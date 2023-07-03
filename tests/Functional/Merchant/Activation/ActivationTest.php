<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use App;
use Mail;
use Queue;
use Config;
use Mockery;

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Base\EsDao;
use RZP\Models\Card\Network;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Models\Merchant\Detail;
use RZP\Services\Mock\Raven;
use RZP\Services\RazorXClient;
use RZP\Services\HubspotClient;
use RZP\Models\Currency\Currency;
use RZP\Jobs\FundAccountValidation;
use RZP\Jobs\SendSubMerchantActivatedEventsToSegment;
use RZP\Models\Admin\Permission\Name;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Document\Type;
use RZP\Mail\Merchant\RejectionSettlement;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Models\Merchant\Detail\PennyTesting;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Mail\Admin\NotifyActivationSubmission;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Models\Merchant\Methods\Repository as MethodRepo;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use \RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\FundAccount\Validation\Entity as ValidationEntity;
use RZP\Models\Workflow\Observer\Constants as ObserverConstants;
use RZP\Models\Workflow\Observer\MerchantActivationStatusObserver;
use RZP\Models\Merchant\Detail\Constants as MerchantDetailsConstant;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;
use RZP\Mail\Merchant\NeedsClarificationEmail as NeedsClarificationEmail;


/**
 * todo, need to add test cases for VA Emails (https://razorpay.atlassian.net/browse/RX-1025)
 */
class ActivationTest extends OAuthTestCase
{
    use TerminalTrait;
    use PartnerTrait;
    use CustomBrandingTrait;
    use EntityActionTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use FundAccountValidationTrait;
    use WorkflowTrait;
    use FreshdeskTrait;
    use HeimdallTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';
    const RZP_ORG                   = '100000razorpay';
    const MERCHANT_ACTIVATED_WORKFLOW_DATA = 'MERCHANT_ACTIVATED_WORKFLOW_DATA';
    const MERCHANT_ACTIVATED_ES_DATA = 'MERCHANT_ACTIVATED_ES_DATA';

    const AXIS_ORG_ID           = 'CLTnQqDj9Si8bx';
    const ICICI_ORG_ID          = 'EKUZMBUtgInwi0';
    const SIB_ORG_ID            = 'HrgeWjbnzZefSN';
    const AXIS_EASYPAY_ORG_ID   = 'ISCkwbk39MdTk5';
    const KOTAK_ORG_ID          = 'IUXvshap3HbzOs';

    protected $esClient;

    protected $esDao;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ActivationTestData.php';

        parent::setUp();

        $this->fixtures->create('org:hdfc_org');

        $this->fixtures->pricing->createEmiPricingPlan();

        $this->enableRazorXTreatmentForActivation();

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();

        Config::set('services.kafka.producer.mock', true);
    }

    protected function enableRazorXTreatmentForActivation()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY or
                        $feature === RazorxTreatment::LITE_ONBOARDING or
                        $feature === RazorxTreatment::UPDATED_LITE_ONBOARDING)
                    {
                        return 'on';
                    }

                    return 'off';
                }));
    }

    public function testInstantActivationWithActivationFormMilestoneAsL1Submission()
    {
        $merchant = $this->fixtures->create('merchant', [
            'pricing_plan_id' => '1hDYlICobzOCYt'
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => $merchant->getId()
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant->getId(),
            'promoter_pan'      => 'EBPPK8222K',
            'promoter_pan_name' => 'User 1',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->id);

        $this->ba->proxyAuth('rzp_test_' . $merchant->id, $merchantUser['id']);

        $this->startTest();
    }

    public function testAddAppUrls()
    {
        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddPaymentsAvenue()
    {
        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddComplianceConsentAndWebsiteNotReady()
    {
        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testAddSocialMediaAndWhatsappUrls()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();
    }

    public function testInstantActivationWithInvalidActivationFormMilestone()
    {
        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testInstantActivationWithNullActivationFormMilestone()
    {
        $merchant = $this->fixtures->create('merchant', [
            'pricing_plan_id' => '1hDYlICobzOCYt'
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => $merchant->getId()
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->id);

        $this->ba->proxyAuth('rzp_test_' . $merchant->id, $merchantUser['id']);

        $this->startTest();
    }

    public function testMerchantActivationCategoriesResponseForAdminAuth()
    {
        $merchant = $this->fixtures->create('merchant');

        // allow admin to access merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminProxyAuth($merchant->getId(), 'rzp_test_' . $merchant->getId());

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

    public function  testGetActivationDetailsForSupportRoleUserFail()
    {
        $merchant = $this->fixtures->create('merchant');

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'support');

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $this->startTest();
    }

    public function  testGetActivationDetailsWithPartnerKycLockedForProprietorship()
    {
        $merchant = $this->fixtures->create('merchant', ['partner_type' => MerchantConstants::AGGREGATOR]);

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'owner');

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_type' => 1
        ]);

        $this->fixtures->create('partner_activation', ['merchant_id' => $merchantId,'locked' => true]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $this->startTest();
    }

    public function  testGetActivationDetailsWithPartnerKycLockedForPvtLtd()
    {
        $merchant = $this->fixtures->create('merchant', ['partner_type' => MerchantConstants::AGGREGATOR]);

        $user = $this->fixtures->create('user');

        $merchantId = $merchant['id'];

        $userID = $user['id'];

        $this->createMerchantUserMapping($userID, $merchantId, 'owner');

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_type' => 4
        ]);

        $this->fixtures->create('partner_activation', ['merchant_id' => $merchantId,'locked' => true]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $userID);

        $this->startTest();
    }

    public function testPostInstantActivation()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $terminalId = $this->fixtures->create('terminal', [
            'merchant_id' => $merchantId,
            'gateway'     => 'hitachi',
            'category'    => '5399',
            'enabled'     => '1',
        ])['id'];

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'promoter_pan' => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
            ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->mockHubSpotClient('trackL1ContactProperties');

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->getHoldFunds());

        $this->assertFalse($merchant->merchantDetail->isSubmitted());

        $this->assertEquals($merchant->getWebsite(), 'https://example.com');
        $this->assertEquals($merchant->getBillingLabel(), 'tsest123');

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getWebsite(), 'https://example.com');

        // assert legal entity data
        $legalEntity = $this->getDbLastEntity('legal_entity');

        $this->assertEquals($merchant->getLegalEntityId(), $legalEntity->getId());
        $this->assertEquals(1, $legalEntity->getBusinessTypeValue());
        $this->assertEquals($legalEntity->getMcc(), 5691);
        $this->assertEquals('ecommerce', $legalEntity->getBusinessCategory());
        $this->assertEquals('fashion_and_lifestyle', $legalEntity->getBusinessSubcategory());

        // assert terminals were disabled - we have a requirement that whenever merchant mcc changes,
        // to disable old mcc hitachi terminals of that merchant.
        //merchant category/category2 is getting auto updated by directly editing the merchant entity and not calling
        // merchant->edit.
        // hence this test to assert even in the automatic MERCHANT_AUTO_UPDATE_SUBCATEGORY_METADATA flow
        // the above requirements are met

        $terminal = $this->getEntityById('terminal', $terminalId, true);
        $this->assertFalse($terminal['enabled']);
    }

    // Whitelist Activation flow
    public function testPostInstantActivationDefaultMethodsBasedOnCategory8931Others()
    {
        $this->testData[__FUNCTION__] = $this->testData['testPostInstantActivationDefaultMethodsBasedOnCategory'];

        // by setting below business details, merchant category will be updated to 8931, others (See autoUpdateMerchantCategoryDetailsIfApplicable and BusinessSubCategoryMetaData.php)
        $this->testData[__FUNCTION__]['request']['content']['business_category'] = 'financial_services';
        $this->testData[__FUNCTION__]['request']['content']['business_subcategory'] = 'accounting';

        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'poi_verification_status' => 'verified',
            ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [], // emi disabled
            'prepaid_card'  => true,
            'paylater'      => true,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'cardless_emi'  => false,
            'debit_emi_providers'=> ['HDFC' => 0],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $cardNetworks = $methodsArray['card_networks'];

        $expectedCardNetworks =  [
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);

        $this->assertArraySelectiveEquals(['HDFC' => 0], $methodsArray['debit_emi_providers']);
    }

    // Whitelist Activation flow, all methods enabled
    public function testActivationDefaultMethodsBasedOnCategor8220College()
    {
        $this->testData[__FUNCTION__] = $this->testData['testPostInstantActivationDefaultMethodsBasedOnCategory'];

        // by setting below business details, merchant category will be updated to 8200, college (See autoUpdateMerchantCategoryDetailsIfApplicable and BusinessSubCategoryMetaData.php)
        $this->testData[__FUNCTION__]['request']['content']['business_category'] = 'education';
        $this->testData[__FUNCTION__]['request']['content']['business_subcategory'] = 'college';

        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'poi_verification_status' => 'verified',
            ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $methodsArray = ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'prepaid_card'  => true,
            'paylater'      => true,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'debit_emi_providers'=> ['HDFC' => 1],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $cardNetworks = $methodsArray['card_networks'];

        $expectedCardNetworks =  [
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);

        $this->assertArraySelectiveEquals(['credit', 'debit'], $methodsArray['emi']);

        $this->assertArraySelectiveEquals(['HDFC' => 1], $methodsArray['debit_emi_providers']);
    }

    // GreyList Activation flow
    public function testActivationDefaultMethodsBasedOnCategory6211MutualFunds()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6211';
        $data['category2'] = 'mutual_funds';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => false,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [], // emi disabled
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'cardless_emi'  => false,
            'debit_emi_providers'=> [],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $cardNetworks = $methodsArray['card_networks'];

        $expectedCardNetworks =  [
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);

        $this->assertArraySelectiveEquals(['HDFC' => 0], $methodsArray['debit_emi_providers']);
    }

    // Greylisted flow
    public function testActivationDefaultMethodsBasedOnCategory6300Insurance()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6300';
        $data['category2'] = 'insurance';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'prepaid_card'  => true,
            'paylater'      => true,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'debit_emi_providers'=> ['HDFC' => 1],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $cardNetworks = $methodsArray['card_networks'];

        $expectedCardNetworks =  [
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);

        $this->assertArraySelectiveEquals(['credit', 'debit'], $methodsArray['emi']);

        $this->assertArraySelectiveEquals(['HDFC' => 1], $methodsArray['debit_emi_providers']);
    }

    public function testActivationDefaultMethodsBasedOnCategory5094()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '5094';
        $data['category2'] = 'ecommerce';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => true,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'prepaid_card'  => true,
            'paylater'      => true,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'cardless_emi'  => false,
            'debit_emi_providers'=> [],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $this->assertArraySelectiveEquals(['HDFC' => 0], $methodsArray['debit_emi_providers']);
    }

    public function testActivationDefaultMethodsBasedOnCategory4411()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '4411';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'prepaid_card'  => true,
            'paylater'      => true,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'cardless_emi'  => true,
            'debit_emi_providers'=> [],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $this->assertArraySelectiveEquals(['HDFC' => 1], $methodsArray['debit_emi_providers']);
    }

    public function testActivationDefaultMethodsBasedOnCategory5960()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '5960';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated'
        );

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'prepaid_card'  => true,
            'paylater'      => false,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'amazonpay'     => false,
            'cardless_emi'  => true,
            'debit_emi_providers' => [],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);
    }

    public function testActivationDefaultMethodsBasedOnCategory8661Others()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '8661';
        $data['category2'] = 'others';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'prepaid_card'  => true,
            'paylater'      => false,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'cardless_emi'  => true,
            'amazonpay'     => false,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $this->assertArraySelectiveEquals(['HDFC' => 1], $methodsArray['debit_emi_providers']);
    }

    public function testActivationDefaultMethodsBasedOnCategory5912pharma()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '5912';
        $data['category2'] = 'pharma';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'amex'          => false,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'prepaid_card'  => true,
            'paylater'      => true,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'phonepe'       => false,
            'cardless_emi'  => false,
            'debit_emi_providers'=> [],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $this->assertArraySelectiveEquals(['HDFC' => 0], $methodsArray['debit_emi_providers']);
    }

    public function testActivationDefaultMethodsBasedOnCategoryBlacklisted()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6051';
        $data['category2'] = 'cryptocurrency';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => false,
            'debit_card'    => false,
            'amex'          => false,
            'netbanking'    => false,
            'upi'           => false,
            'emi'           => [], // emi disabled
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => false,
            'freecharge'    => false,
            'jiomoney'      => false,
            'mobikwik'      => false,
            'mpesa'         => false,
            'olamoney'      => false,
            'payumoney'     => false,
            'payzapp'       => false,
            'sbibuddy'      => false,
            'phonepe'       => false,
            'cardless_emi'  => false,
            'debit_emi_providers'=> [],
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);

        $cardNetworks = $methodsArray['card_networks'];

        $expectedCardNetworks =  [
            Network::AMEX   =>  0,
        ];

        $this->assertArraySelectiveEquals($expectedCardNetworks, $cardNetworks);

        $this->assertArraySelectiveEquals(['HDFC' => 0], $methodsArray['debit_emi_providers']);
    }

    public function testActivationDefaultMethodsBasedOnAxisOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = self::AXIS_ORG_ID;

        // create org

        $org = $this->fixtures->create('org', ['id' => $orgId]);

        $planId = '1hDYlICobzOCYt';

        // create pricing plan for org

        $this->fixtures->pricing->createStandardPricingPlanForDifferentOrg($planId, $orgId);

        $authToken = $this->getAuthTokenForOrg($org);

        // assign org standard pricing plan to merchant

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
            'pricing_plan_id' => $planId
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $merchantId]);

        $methods = $this->fixtures->edit('methods', $merchantId, ['bank_transfer' => 0]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6051';
        $data['category2'] = 'cryptocurrency';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'netbanking'    => true,
            'upi'           => true,
            'emandate'      => false,
            'emi'           => [],
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => false,
            'freecharge'    => false,
            'jiomoney'      => false,
            'mobikwik'      => false,
            'mpesa'         => false,
            'olamoney'      => false,
            'payumoney'     => false,
            'payzapp'       => false,
            'sbibuddy'      => false,
            'phonepe'       => false,
            'phonepeswitch' => false,
            'bank_transfer' => false,
            'cardless_emi'  => false,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);
    }

    public function testActivationDefaultMethodsBasedOnIciciOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = self::ICICI_ORG_ID;

        // create org

        $org = $this->fixtures->create('org', ['id' => $orgId]);

        $planId = '1hDYlICobzOCYt';

        // create pricing plan for org

        $this->fixtures->pricing->createStandardPricingPlanForDifferentOrg($planId, $orgId);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $authToken = $this->getAuthTokenForOrg($org);

        // assign org standard pricing plan to merchant

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
            'pricing_plan_id' => $planId
        ]);

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $merchantId]);

        $methods = $this->fixtures->edit('methods', $merchantId, ['bank_transfer' => 0]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6051';
        $data['category2'] = 'cryptocurrency';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'netbanking'    => true,
            'upi'           => true,
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => true,
            'freecharge'    => true,
            'jiomoney'      => true,
            'mobikwik'      => true,
            'mpesa'         => true,
            'olamoney'      => true,
            'payumoney'     => true,
            'payzapp'       => false,
            'sbibuddy'      => true,
            'cardless_emi'  => true,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);
    }

    public function testActivationDefaultMethodsBasedOnSibOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = self::SIB_ORG_ID;

        // create org

        $org = $this->fixtures->create('org', ['id' => $orgId]);

        $planId = '1hDYlICobzOCYt';

        // create pricing plan for org

        $this->fixtures->pricing->createStandardPricingPlanForDifferentOrg($planId, $orgId);

        $authToken = $this->getAuthTokenForOrg($org);

        // assign org standard pricing plan to merchant

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
            'pricing_plan_id' => $planId
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);


        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $merchantId]);

        $methods = $this->fixtures->edit('methods', $merchantId, ['bank_transfer' => 0]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6051';
        $data['category2'] = 'cryptocurrency';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'amex'          => false,
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => false,
            'freecharge'    => false,
            'jiomoney'      => false,
            'mobikwik'      => false,
            'mpesa'         => false,
            'olamoney'      => false,
            'payumoney'     => false,
            'payzapp'       => false,
            'sbibuddy'      => false,
            'phonepeswitch' => false,
            'bank_transfer' => false,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);
    }

    public function testActivationDefaultMethodsBasedOnAxisEasypayOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = self::AXIS_EASYPAY_ORG_ID;

        // create org

        $org = $this->fixtures->create('org', ['id' => $orgId]);

        $planId = '1hDYlICobzOCYt';

        // create pricing plan for org

        $this->fixtures->pricing->createStandardPricingPlanForDifferentOrg($planId, $orgId);

        $authToken = $this->getAuthTokenForOrg($org);

        // assign org standard pricing plan to merchant

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
            'pricing_plan_id' => $planId
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $merchantId]);

        $methods = $this->fixtures->edit('methods', $merchantId, ['bank_transfer' => 0]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6051';
        $data['category2'] = 'cryptocurrency';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'amex'          => false,
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => false,
            'freecharge'    => false,
            'jiomoney'      => false,
            'mobikwik'      => false,
            'mpesa'         => false,
            'olamoney'      => false,
            'payumoney'     => false,
            'payzapp'       => false,
            'sbibuddy'      => false,
            'phonepeswitch' => false,
            'bank_transfer' => false,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);
    }

    public function testActivationDefaultMethodsBasedOnKotakOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = self::KOTAK_ORG_ID;

        // create org

        $org = $this->fixtures->create('org', ['id' => $orgId]);

        $planId = '1hDYlICobzOCYt';

        // create pricing plan for org

        $this->fixtures->pricing->createStandardPricingPlanForDifferentOrg($planId, $orgId);

        $authToken = $this->getAuthTokenForOrg($org);

        // assign org standard pricing plan to merchant

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
            'pricing_plan_id' => $planId
        ]);

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->create('methods:default_methods', ['merchant_id' => $merchantId]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $methods = $this->fixtures->edit('methods', $merchantId, ['bank_transfer' => 0]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $data['category'] = '6051';
        $data['category2'] = 'cryptocurrency';
        $data['activated'] = 0;

        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            'activated');

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest($testData);

        $methodsArray =  ((new MethodRepo)->find($merchantId))->toArray();

        $expectedMethods = [
            'credit_card'   => true,
            'debit_card'    => true,
            'netbanking'    => true,
            'upi'           => true,
            'emi'           => [],
            'amex'          => false,
            'prepaid_card'  => false,
            'paylater'      => false,
            'airtelmoney'   => false,
            'freecharge'    => false,
            'jiomoney'      => false,
            'mobikwik'      => false,
            'mpesa'         => false,
            'olamoney'      => false,
            'payumoney'     => false,
            'payzapp'       => false,
            'sbibuddy'      => false,
            'phonepeswitch' => false,
            'bank_transfer' => false,
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methodsArray);
    }

    public function testPostInstantActivationBlockedOrg()
    {
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = 'CLTnQqDj9Si8bx';

        $this->fixtures->create('org', ['id' => $orgId]);

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testBusinessWebsiteUpdate()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $website = 'http://abc.com';

        $this->fixtures->edit('merchant', $merchantId, ['website' => $website]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'             => $merchantId,
            'business_website'        => $website,
            'promoter_pan'            => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals($merchant->getWebsite(), 'https://example.com');

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getWebsite(), 'https://example.com');

        $this->assertContains('example.com', $merchant->getWhitelistedDomains());

        $this->assertNotContains('abc.com', $merchant->getWhitelistedDomains());
    }

    public function testPostInstantActivationWithBalanceCreation()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $balanceId = '12212121';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'             => $merchantId,
            'promoter_pan'            => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $timeStamp = Carbon::now()->getTimestamp();

        DB::connection('live')->table('balance')
          ->insert([
                       'id' => $balanceId,
                       'merchant_id' => $merchantId,
                       'type' => \RZP\Models\Merchant\Balance\Type::PRIMARY,
                       'currency' => Currency::INR,
                       'name' => 'test',
                       'balance' => 0,
                       'created_at' => $timeStamp,
                       'updated_at' => $timeStamp,
                   ]);


        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->mockHubSpotClient('trackL1ContactProperties');

        $this->startTest();

        $merchantBalance = $this->getDbEntity('balance', [
            'merchant_id' => $merchantId,
        ], 'live');

        $this->assertEquals($merchantBalance->getId(), $balanceId);

    }

    public function testInstantActivationForForUnRegisteredTORegisteredSwitch()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        Config::set('applications.kyc.mock', true);

        Config::set('applications.kyc.pan_authentication', MerchantDetailsConstant::SUCCESS);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

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

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id' => $merchantId,
            ]
        );

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testInstantActivationForForRegisteredTOUnRegisteredSwitch()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        Config::set('applications.kyc.mock', true);

        Config::set('applications.kyc.pan_authentication', MerchantDetailsConstant::SUCCESS);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $testData = $this->testData['testInstantActivationForUnregisteredBusinessForOlderMerchant'];

        $this->startTest($testData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNull($merchantDetail->getActivationFLow());
        $this->assertNull($merchantDetail->getInternationalActivationFlow());
    }

    public function testInstantActivationForUnregisteredBusinessForOlderMerchant()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        Config::set('applications.kyc.mock', true);

        Config::set('applications.kyc.pan_authentication', MerchantDetailsConstant::SUCCESS);

        $this->fixtures->create('merchant_detail', ['merchant_id'             => $merchantId,
                                                    'poi_verification_status' => '',
                                                    'promoter_pan'            => 'ABCPE0000Z',
                                                    'business_name'           => 'business_name',
                                                    'business_dba'            => 'test123',
                                                    'business_type'           => 11,
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNull($merchantDetail->getActivationFLow());
        $this->assertNull($merchantDetail->getInternationalActivationFlow());
    }

    public function testIAForUnregisteredBusinessFeatureEnabled()
    {
        $this->markTestSkipped("instant activation for Un-reg will happen in async since since pan verification is done via BVS now");
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $tests = [
            'testIAForUnregisteredBusinessFeatureEnabledNameMisMatch'     => MerchantDetailsConstant::SUCCESS,
            'testIAForUnregisteredBusinessFeatureEnabledIncorrectDetails' => MerchantDetailsConstant::INCORRECT_DETAILS,
            'testIAForUnregisteredBusinessFeatureEnabledTimeout'          => MerchantDetailsConstant::FAILURE,
            'testIAForUnregisteredBusinessFeatureEnabled'                 => MerchantDetailsConstant::SUCCESS, // at bottom because once successful, the request can not be tried again
        ];

        Config::set('applications.kyc.mock', true);

        foreach ($tests as $test => $mockStatus)
        {

            Config::set('applications.kyc.pan_authentication', $mockStatus);

            $testData = $this->testData[$test];

            $this->runRequestResponseFlow($testData);
        }
    }

    public function testIAForUnregisteredBusinessFromKycService()
    {
        $this->markTestSkipped("pan verification is done via BVS now");
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $tests = [
            'testIAForUnregisteredBusinessFeatureEnabledNameMisMatch'     => MerchantDetailsConstant::SUCCESS,
            'testIAForUnregisteredBusinessFeatureEnabledIncorrectDetails' => MerchantDetailsConstant::INCORRECT_DETAILS,
            'testIAForUnregisteredBusinessFeatureEnabledTimeout'          => MerchantDetailsConstant::FAILURE,
            'testIAForUnregisteredBusinessFeatureEnabled'                 => MerchantDetailsConstant::SUCCESS, // at bottom because once successful, the request can not be tried again
        ];

        foreach ($tests as $test => $mockStatus)
        {
            Config::set('applications.kyc.pan_authentication', $mockStatus);

            Config::set('applications.kyc.mock', true);

            $testData = $this->testData[$test];

            $this->runRequestResponseFlow($testData);
        }
    }

    public function testIAForRegisteredBusinessFeatureEnabledNameMisMatch()
    {
        self::markTestSkipped("personal pan verification is done via BVS now");
        $this->instantActivationForRegistered(__FUNCTION__, MerchantDetailsConstant::SUCCESS);
    }

    public function testIAForRegisteredBusinessFeatureEnabledIncorrectDetails()
    {
        self::markTestSkipped("personal pan verification is done via BVS now");
        $this->instantActivationForRegistered(__FUNCTION__, MerchantDetailsConstant::INCORRECT_DETAILS);
    }

    public function testIAForRegisteredBusinessFeatureEnabledTimeout()
    {
        self::markTestSkipped("personal pan verification is done via BVS now");
        $this->instantActivationForRegistered(__FUNCTION__, MerchantDetailsConstant::FAILURE);
    }

    public function testIAForRegisteredBusinessSuccessCase()
    {
        self::markTestSkipped("personal pan verification is done via BVS now");
        $this->instantActivationForRegistered(__FUNCTION__, MerchantDetailsConstant::SUCCESS);
    }

    private function instantActivationForRegistered(string $test, string $mockStatus)
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        Config::set('applications.kyc.company_pan_authentication', $mockStatus);

        Config::set('applications.kyc.pan_authentication', $mockStatus);

        Config::set('applications.kyc.mock', true);

        $testData = $this->testData[$test];

        $this->runRequestResponseFlow($testData);
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

    public function mockRazorX(string $functionName, string $featureName, string $variant, $merchantId = '1cXSLlUU8V9sXl')
    {
        $featureVariantMap = [$featureName => $variant];

        $this->mockRazorXMultiFeature($functionName, $featureVariantMap, $merchantId);
    }

    public function mockRazorXMultiFeature(string $functionName, array $featureVariantMap, $merchantId = '1cXSLlUU8V9sXl')
    {
        $testData = &$this->testData[$functionName];

        $localIdVariantMap = [];

        foreach ($featureVariantMap as $featureName => $variant)
        {
            $uniqueLocalId                     = RazorXClient::getLocalUniqueId($merchantId, $featureName, Mode::TEST);
            $localIdVariantMap[$uniqueLocalId] = $variant;
        }

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => json_encode($localIdVariantMap)];
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);
        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals("pending", $merchant->merchantDetail->getSubscriptionsActivationStatus());

    }

    public function testSmartCollectActivationForUnregisteredMerchants()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->edit('merchant',$merchantId,['activated' => 1]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'business_type'=> 2]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
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
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);
        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals("pending", $merchant->merchantDetail->getMarketplaceActivationStatus());

    }

    public function testInstantActivationWithBlacklistedCategoryForEmi()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'             => $merchantId,
            'promoter_pan'            => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantMethods = $this->getDbEntityById('merchant', $merchantId)->getMethods();

        $this->assertFalse($merchantMethods->isEmiEnabled());
    }

    public function testInstantActivationWithWhitelistedCategoryForEmi()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'             => $merchantId,
            'promoter_pan'            => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
        ]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testSaveCommonMerchantDetailsWhenPartnerActivationLocked()
    {
        $this->fixtures->create('partner_activation', [
            'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            'activation_status' => 'under_review',
            'locked'            => true,
            'submitted'         => true
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'         => self::DEFAULT_MERCHANT_ID,
            'promoter_pan'        => 'ABCPE0000Z',
            'bank_account_number' => '123456789012345',
            'bank_branch_ifsc'    => 'ICIC0000001'
        ]);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testSaveUncommonMerchantDetailsWhenPartnerActivationLocked()
    {
        $this->fixtures->create('partner_activation', [
            'merchant_id'       => self::DEFAULT_MERCHANT_ID,
            'activation_status' => 'under_review',
            'locked'            => true,
            'submitted'         => true
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'         => self::DEFAULT_MERCHANT_ID,
            'promoter_pan'        => 'ABCPE0000Z',
            'bank_account_number' => '123456789012345',
            'bank_branch_ifsc'    => 'ICIC0000001'
        ]);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testUpdateActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'promoter_pan'            => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID], $merchantUser['id']);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('greylist', $liveMerchant->merchantdetail->getActivationFlow());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame('greylist', $testMerchant->merchantdetail->getActivationFlow());
    }

    public function testSegmentEventSubmerchantActivated()
    {
        Queue::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $data                      = $this->getKycSubmittedMerchantDetailData($merchantId);
        $data['activation_status'] = 'under_review';
        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('test')->create('methods:default_methods', [
            'merchant_id' => $merchantId
        ]);

        $this->fixtures->user->createUserForMerchant($merchantId);
        $merchant     = $this->getDbEntityById('merchant', $merchantId, 'test');

        $this->fixtures->create('merchant_access_map', ['merchant_id' => $merchantId,]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $testData                   = $this->testData['changeActivationStatus'];
        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $currentActivationStatus = 'activated';
        // under_review to activated
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            $currentActivationStatus);

        $this->startTest($testData);
        // ensure job was triggered
        Queue::assertPushed(SendSubMerchantActivatedEventsToSegment::class);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Submerchant Activated"], true));
                    }));

        // Test worker
        $sendSubMerchantActivatedEventsToSegment = new SendSubMerchantActivatedEventsToSegment($merchant->getId(), $currentActivationStatus);
        $sendSubMerchantActivatedEventsToSegment->handle();

    }

    public function testSkipSegmentEventSubmerchantActivated()
    {
        Queue::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $data                      = $this->getKycSubmittedMerchantDetailData($merchantId);
        $data['activation_status'] = 'under_review';
        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('test')->create('methods:default_methods', [
            'merchant_id' => $merchantId
        ]);

        $this->fixtures->user->createUserForMerchant($merchantId);
        $merchant     = $this->getDbEntityById('merchant', $merchantId, 'test');

        $this->fixtures->create('merchant_access_map', ['merchant_id' => $merchantId,]);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $testData                   = $this->testData['changeActivationStatus'];
        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $currentActivationStatus = 'needs_clarification';
        // under_review to needs_clarification
        $this->changeActivationStatus(
            $testData['request']['content'],
            $testData['response']['content'],
            $currentActivationStatus);

        $this->startTest($testData);

        // ensure job was triggered
        Queue::assertPushed(SendSubMerchantActivatedEventsToSegment::class);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
                    ->method('pushIdentifyAndTrackEvent');

        // Test worker
        $sendSubMerchantActivatedEventsToSegment = new SendSubMerchantActivatedEventsToSegment($merchant->getId(), $currentActivationStatus);
        $sendSubMerchantActivatedEventsToSegment->handle();

    }

    public function testUpdateCategoryDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID], $merchantUser['id']);

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
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'             => self::DEFAULT_MERCHANT_ID,
            'contact_email'           => 'test@razorpay.com',
            'promoter_pan'            => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
        ]);

        $this->ba->adminAuth();
        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGreylistInstantActivation()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'             => self::DEFAULT_MERCHANT_ID,
            'contact_email'           => 'test@razorpay.com',
            'promoter_pan'            => 'ABCPE0000Z',
            'poi_verification_status' => 'verified',
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
            [MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST,
             'promoter_pan'                   => 'ABCPE0000Z',
             'poi_verification_status'        => 'verified',]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID], $merchantUser['id']);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('whitelist', $liveMerchant->merchantdetail->getActivationFlow());
    }

    public function setupKycSubmissionForInstantlyActivatedMerchant($merchantId, $customMerchantDetailAttributes = [])
    {
        $data = $this->getInstantlyActivatedMerchantDetailData($merchantId);

        // Adding the file upload attributes for simplicity of the test
        $otherMerchantDetailAttributes = [
            'address_proof_url'    => '124',
            'business_pan_url'     => '124',
            'business_proof_url'   => '124',
            'promoter_address_url' => '124',
        ];

        $data = array_merge($data, $otherMerchantDetailAttributes, $customMerchantDetailAttributes);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'promoter_address_url',
            'merchant_id'   => $merchantId,
        ]);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $this->createDocumentEntities($merchantId, ['address_proof_url', 'business_pan_url', 'business_proof_url', 'promoter_address_url']);
    }

    public function testAutoSubmitPartnerKycFromMerchantKycForm()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->fixtures->merchant->editEntity('merchant', $merchantId, ['partner_type' => MerchantConstants::AGGREGATOR]);

        $this->startTest();

        $testData = $this->testData['submitKyc'];

        $this->startTest($testData);

        $partnerActivation = $this->getDbEntityById('partner_activation', $merchantId);

        $this->assertEquals($partnerActivation->getActivationStatus(), 'under_review');
    }

    public function testKycSubmissionForInstantlyActivatedMerchantAovAbsent()
    {
        $this->enableRazorXTreatmentForActivation();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $testData = $this->testData['submitKyc'];

        $this->startTest($testData);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $merchantAov = $merchantDetail->avgOrderValue;

        $this->assertEquals(-1, $merchantAov->getMinAov());

        $this->assertEquals(-1, $merchantAov->getMaxAov());
    }

    public function testKycSubmissionForInstantlyActivatedMerchantBankAccountNameAbsent()
    {
        $this->enableRazorXTreatmentForActivation();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $testData = $this->testData['submitKyc'];

        $this->startTest($testData);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertNotNull($merchantDetail->getBankAccountName());
    }

    public function testKycSubmissionForInstantlyActivatedBusinessParentCategory()
    {
        $this->enableRazorXTreatmentForActivation();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertNotNull($merchantDetail->getBankAccountName());

        $businessDetail = $this->getLastEntity('merchant_business_detail', true);

        $this->assertEquals($businessDetail['blacklisted_products_category'], 'none of the above');
    }

    public function testKycSubmissionBusinessParentCategoryChange()
    {
        $this->enableRazorXTreatmentForActivation();

        $merchantId = '1cXSLlUU8V9sXl';

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $activationRequest = [
            'url'     => '/merchant/activation/',
            'method'  => 'post',
            'content' => [
                'business_parent_category' => 'healthcare_wellness_fitness',
            ],
        ];

        $content = $this->makeRequestAndGetContent($activationRequest);

        $this->assertNull($content['business_category']);

        $this->assertNull($content['business_subcategory']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertNotNull($merchantDetail->getBankAccountName());

        $businessDetail = $this->getLastEntity('merchant_business_detail', true);

        $this->assertEquals($businessDetail['blacklisted_products_category'], null);
    }

    public function testKycSubmissionForInstantlyActivatedMerchantForRazorpayOrg()
    {
        Mail::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $testData = $this->testData['submitKyc'];

        $this->startTest($testData);
        Mail::assertQueued(NotifyActivationSubmission::class);
    }

    public function testAllowNonDSMerchantsForActivation()
    {
        $this->ba->adminAuth();

        $merchantSignupRequest = [
            'content' => [
                'id'          => '1X4hRFHFx4UiXt',
                'name'        => 'Tester',
                'email'       => 'test@localhost.com',
                'coupon_code' => 'RANDOM-123',
            ],
            'url'     => '/merchants',
            'method'  => 'POST',
        ];


        $this->fixtures->org->addFeatures([FeatureConstants::ORG_PROGRAM_DS_CHECK], "100000razorpay");

        $response = $this->makeRequestAndGetContent($merchantSignupRequest);

        Config::set('applications.test_case.execution', false);

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'website' => 'abc.com',
            'category' => 1100,
            'billing_label' => 'labore',
            'transaction_report_email' => 'test@razorpay.com',
            'created_at' => 1670889499
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttributes);

        $this->fixtures->on('live')->edit('merchant_detail', $merchantId, [
            'submitted'           => true,
            'bank_branch_ifsc'    => 'CBIN0281697',
            'bank_account_number' => '0002020000304030434',
            'bank_account_name'   => 'random name',
            'contact_mobile'      => '9999999999',
            'business_category'   => 'financial_services',
            'business_subcategory'=> 'accounting',
        ]);

        $this->fixtures->on('test')->edit('merchant_detail', $merchantId, [
            'submitted'           => true,
            'bank_branch_ifsc'    => 'CBIN0281697',
            'bank_account_number' => '0002020000304030434',
            'bank_account_name'   => 'random name',
            'contact_mobile'      => '9999999999',
            'business_category'   => 'financial_services',
            'business_subcategory'=> 'accounting',
        ]);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->ba->adminAuth();

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchantId);

        $response = $this->makeRequestAndGetContent($activationRequest);

        $this->assertEquals("activated", $response['activation_status']);
    }


    public function testKycSubmissionForInstantlyActivatedMerchantForCustomOrg()
    {
        Mail::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $org = $this->createCustomBrandingOrgAndAssignMerchant($merchantId);

        $this->startTest();

        $testData = $this->testData['submitKyc'];

        $this->startTest($testData);
        Mail::assertQueued(NotifyActivationSubmission::class);

    }

    public function testKycSubmissionWhenPoaIsOcrVerified()
    {
        $this->kycSubmissionWithSuccessCases('verified', 'verified');
    }

    public function testPOASubmissionForUnRegisteredMerchantWithAadhaar()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $attributes = [
            'business_type'           => 11,
            'merchant_id'             => $merchantId,
            'poi_verification_status' => 'verified',
        ];

        $this->validatePOASubmission([Type::AADHAR_FRONT, Type::AADHAR_BACK], $merchantId, $attributes);
    }

    public function testPOASubmissionForRegisteredMerchantWithAadhaar()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $attributes = [
            'business_type'        => 1,
            'merchant_id'          => $merchantId,
            'promoter_address_url' => null
        ];

        $documentTypesOtherThenPOA = ['address_proof_url', 'business_pan_url', 'business_proof_url'];

        $this->validatePOASubmission([Type::AADHAR_FRONT, Type::AADHAR_BACK], $merchantId, $attributes, $documentTypesOtherThenPOA);
    }

    public function testPOASubmissionForRegisteredMerchantWithPassport()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $attributes = [
            'business_type'        => 1,
            'merchant_id'          => $merchantId,
            'promoter_address_url' => null
        ];

        $documentTypesOtherThenPOA = ['address_proof_url', 'business_pan_url', 'business_proof_url'];

        $this->validatePOASubmission([Type::PASSPORT_BACK, Type::PASSPORT_FRONT], $merchantId, $attributes, $documentTypesOtherThenPOA);
    }

    public function testPOASubmissionForUnRegisteredMerchantWithVoterId()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $attributes = [
            'business_type'           => 11,
            'merchant_id'             => $merchantId,
            'promoter_address_url'    => null,
            'poi_verification_status' => 'verified',
        ];

        $this->validatePOASubmission([Type::VOTER_ID_FRONT, Type::VOTER_ID_BACK], $merchantId, $attributes);
    }

    public function testPOASubmissionForRegisteredMerchantWithVoterId()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $attributes = [
            'business_type'        => 1,
            'merchant_id'          => $merchantId,
            'promoter_address_url' => null
        ];

        $documentTypesOtherThenPOA = ['address_proof_url', 'business_pan_url', 'business_proof_url'];

        $this->validatePOASubmission([Type::VOTER_ID_FRONT, Type::VOTER_ID_BACK], $merchantId, $attributes, $documentTypesOtherThenPOA);
    }

    public function testPOASubmissionForRegisteredMerchantWithPromoterAddress()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $attributes = [
            'business_type'        => 1,
            'merchant_id'          => $merchantId,
            'promoter_address_url' => null
        ];

        $documentTypesOtherThenPOA = ['address_proof_url', 'business_pan_url', 'business_proof_url'];

        $this->validatePOASubmission([Type::PROMOTER_ADDRESS_URL], $merchantId, $attributes, $documentTypesOtherThenPOA);
    }

    private function createPOACanSubmitData(array $attributes)
    {
        $this->fixtures->create('merchant_detail:filledEntity', $attributes);

        $plan = $this->createZeroFundAccountValidationPricingPlan();

        $this->createBalanceForSharedMerchant();

        $this->fixtures->merchant->editEntity('merchant',
                                              '100000Razorpay',
                                              [
                                                  'pricing_plan_id' => $plan->getPlanId()
                                              ]);
    }

    private function validatePOASubmission(array $documentTypes,
                                           string $merchantId = '1cXSLlUU8V9sXl',
                                           array $attributes = [],
                                           array $documentTypesOtherThenPOA = [])
    {
        $this->createPOACanSubmitData($attributes);

        $this->fixtures->create('merchant_document:multiple',
                                [
                                    'document_types' => $documentTypesOtherThenPOA,
                                    'attributes'     => ['merchant_id' => $merchantId]
                                ]);

        $testSuit = 'validateCanSubmit';

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

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

    public function kycSubmissionWithSuccessCases($poaVerificationStatus, $bankDetailsVerificationStatus = null)
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->createMerchantDocumentEntries($merchantId, 'aadhar_front');

        $this->createMerchantDocumentEntries($merchantId, 'aadhar_back');

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchantId);

        $this->getKycVerificationForPoaVerificationSetup($poaVerificationStatus, $bankDetailsVerificationStatus);

        $testSuits = [
            'testKycSubmissionWhenPoaIsVerified',
            'submitKycActivated'
        ];

        $plan = $this->createZeroFundAccountValidationPricingPlan();

        $this->fixtures->merchant->editEntity('merchant',
                                              '100000Razorpay',
                                              [
                                                  'pricing_plan_id' => $plan->getPlanId()
                                              ]);
        $data = [
            StoreConstants::NAMESPACE                          => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => 1
        ];

        $data = (new StoreCore())->updateMerchantStore("1cXSLlUU8V9sXl",
                                                       $data,
                                                       StoreConstants::INTERNAL);
        foreach ($testSuits as $index => $testSuit)
        {
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

        $this->createBalanceForSharedMerchant();

        $plan = $this->createZeroFundAccountValidationPricingPlan();

        $this->fixtures->merchant->editEntity('merchant',
                                              '100000Razorpay',
                                              [
                                                  'pricing_plan_id' => $plan->getPlanId()
                                              ]);

        $testSuits = [
            'testKycSubmissionWhenPoaIsFailed',
            'submitKyc'
        ];

        foreach ($testSuits as $index => $testSuit)
        {
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
            'poi_verification_status'          => 'verified',
            'bank_details_verification_status' => $bankDetailsVerificationStatus,
        ];
        $data = array_merge($data, $otherMerchantDetailAttributes);
        $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);
    }


    public function testKYCVerificationForInstantlyActivatedMerchant()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $data = $this->getKycSubmittedMerchantDetailData($merchantId);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->fixtures->create('merchant_detail', $data);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $data = $this->getKycSubmittedMerchantData();
        $this->fixtures->on('test')->edit('merchant', $merchantId, $data);
        $this->fixtures->on('live')->edit('merchant', $merchantId, $data);

        $testData = $this->testData['changeActivationStatus'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantId, 'test');

        $this->assertTrue($merchantDetail->isLocked());

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

    public function testReleaseFundsWithParntersBankAccount()
    {
        list($application) = $this->createPartnerMerchantAndSubMerchant(MerchantConstants::AGGREGATOR);

        $data = $this->getInstantlyActivatedMerchantDetailData(Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID);
        $this->fixtures->create('merchant_detail', $data);

        $this->ba->adminAuth();

        $testData                   = &$this->testData[__FUNCTION__];
        $testData['request']['url'] = '/merchants/' . Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID . '/action';

        $data = $this->getInstantlyActivatedMerchantData();
        $this->fixtures->on('test')->edit('merchant', Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, $data);
        $this->fixtures->on('live')->edit('merchant', Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, $data);

        $configAttributes = [
            'SETTLE_TO_PARTNER' => 1,
        ];

        $this->createConfigForPartnerApp($application->getId(), Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID, $configAttributes);

        $config = [
            'deleted_at' => '1576153748'
        ];

        $bankAccount = $this->getDbEntity('bank_account',
                                          ['entity_id'   => Constants::DEFAULT_PLATFORM_MERCHANT_ID,
                                           'merchant_id' => Constants::DEFAULT_PLATFORM_MERCHANT_ID]);

        $this->fixtures->on('test')->edit('bank_account', $bankAccount['id'], $config);

        $bankAccount = $this->getDbEntity('bank_account',
                                          ['entity_id'   => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                                           'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID]);

        $this->fixtures->on('test')->edit('bank_account', $bankAccount['id'], $config);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

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
            Entity::PROMOTER_PAN         => 'ABCPE1234E',
            'poi_verification_status'    => 'verified',
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
            'merchant_id'               => $merchantId,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'fashion_and_lifestyle',
            'promoter_pan'              => 'ABCPE0000Z',
            'promoter_pan_name'         => 'John Doe',
            'activation_status'         => 'instantly_activated',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1'
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
            'promoter_pan'                => 'ABCPE0000Z',
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

    public function testWhitelistInternationalForRiskyBusinessType()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertNull($merchant->convertOnApi());
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

    public function testWhitelistInternationalNonInstantActivation()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->runFixturesForInternationalActivation($merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'whitelist');

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

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

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

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

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

    public function testActivationWithUpdateObserverData()
    {
        // feature not being used
        $this->markTestSkipped();

        $data = [
            'submitted'             => 1,
            'activation_status'     => 'under_review'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail',$data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->setupWorkflow("Activation Workflow",Name::EDIT_ACTIVATE_MERCHANT);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($activationRequest);

        $expectedWorkflow  = $this->getExpectedArraysForWorkflowObserverTestCases(self::MERCHANT_ACTIVATED_WORKFLOW_DATA);

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => 123,
            'fd_instance'   => 'rzpind'
        ]);

        $this->esClient->indices()->refresh();

        $this->assertArraySelectiveEquals($expectedWorkflow, $response);

        $workflowData = $this->getWorkflowData();

        $expectedWorkFlowActionData  = $this->getExpectedArraysForWorkflowObserverTestCases(self::MERCHANT_ACTIVATED_ES_DATA);

        $this->assertArraySelectiveEquals($expectedWorkFlowActionData, $workflowData);

        $this->assertEquals('https://api.razorpay.com/v1/merchant/activation/'.$merchantId.'/activation_status', $workflowData['url']);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->fixtures->create('merchant_freshdesk_tickets', $this->getDefaultFreshdeskArray($merchantId));

        $this->setUpFreshdeskClientMock();

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
            [
                'body' => implode("<br><br>",(new MerchantActivationStatusObserver(
                    [
                        DifferEntity::PAYLOAD => [
                            "activation_status" => 'activated'
                        ],
                        DifferEntity::ENTITY_ID => $merchantId]))->getTicketReplyContent(ObserverConstants::APPROVE, $merchantId)),
            ],
            [

            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
            [],
            [
                'id'        => '123',
                'tags'      => null
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
            [
                'status'    => 4
            ],
            [
                'id'            => '123',
            ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->isActivated());
    }

    public function testActivationRejectedWithUpdateObserverData()
    {
        // feature not being used
        $this->markTestSkipped();

        $data = [
            'submitted'             => 1,
            'activation_status'     => 'under_review'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->setupWorkflow("Activation Workflow",Name::EDIT_ACTIVATE_MERCHANT);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'rejected',
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($activationRequest);

        $expectedWorkflow  = $this->getExpectedArraysForWorkflowObserverTestCases(self::MERCHANT_ACTIVATED_WORKFLOW_DATA);

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => 123,
            'fd_instance'   => 'rzpind'
        ]);

        $this->esClient->indices()->refresh();

        $this->assertArraySelectiveEquals($expectedWorkflow, $response);

        $workflowData = $this->getWorkflowData();

        $expectedWorkFlowActionData  = $this->getExpectedArraysForWorkflowObserverTestCases(self::MERCHANT_ACTIVATED_ES_DATA);

        $this->assertArraySelectiveEquals($expectedWorkFlowActionData, $workflowData);

        $this->assertEquals('https://api.razorpay.com/v1/merchant/activation/'.$merchantId.'/activation_status', $workflowData['url']);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->setUpFreshdeskClientMock();

        $this->fixtures->create('merchant_freshdesk_tickets', $this->getDefaultFreshdeskArray($merchantId));

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
            [],
            [
                'id'        => '123',
                'tags'      => null
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
            [
                'body' => implode("<br><br>",(new MerchantActivationStatusObserver(
                    [
                        DifferEntity::PAYLOAD => [
                            "activation_status" => 'rejected'
                        ],
                        DifferEntity::ENTITY_ID => $merchantId]))->getTicketReplyContent(ObserverConstants::APPROVE, $merchantId)),
            ],
            [

            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
            [
                'status'    => 4
            ],
            [
                'id'            => '123',
            ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertFalse($merchant->isActivated());
    }

    public function testActivationRejectedWithUpdateObserverData1()
    {
        $data = [
            'submitted'             => 1,
            'activation_status'     => 'under_review'
        ];

        $maker = $this->fixtures->create('admin', [
            'name' => "maker name",
            'org_id'    => '100000razorpay',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'on',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $data);

        $merchantId = $merchantDetail->getMerchantId();

        $this->setupWorkflow("Activation Workflow",Name::EDIT_ACTIVATE_MERCHANT);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status/internal',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'rejected',
                'workflow_maker_id' => $maker->getPublicId(),
            ],
        ];

        $this->ba->cmmaAppAuth();

        $response = $this->makeRequestAndGetContent($activationRequest);

        $expectedWorkflow  = $this->getExpectedArraysForWorkflowObserverTestCases(self::MERCHANT_ACTIVATED_WORKFLOW_DATA);

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->esClient->indices()->refresh();

        $this->assertArraySelectiveEquals($expectedWorkflow, $response);

        $workflowData = $this->getWorkflowData();

        $this->assertArraySelectiveEquals([
            'entity_name'   => 'merchant_detail',
            'entity_id'     => $merchantId,
            'maker'         =>'maker name',
            'maker_id'      => $maker->getId(),
            'maker_type'    => 'admin',
            'diff' => [
                'old' => [
                    'activation_status' => 'under_review',
                ],
                'new' => [
                    'activation_status' => 'rejected',
                ]
            ],
        ], $workflowData);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals('under_review', $merchant->merchantDetail->getActivationStatus());

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertEquals('rejected', $merchant->merchantDetail->getActivationStatus());

        $this->assertFalse($merchant->isActivated());
    }

    protected function mockSplitzTreatment($output)
    {
        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    protected function runFixturesForInternationalActivation(string $merchantId, string $orgId = Org::RZP_ORG)
    {
        $this->fixtures->edit('merchant', $merchantId, ['international' => 0, 'org_id' => $orgId]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'poi_verification_status' => 'verified']);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => $merchantId
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);
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
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'settlement_service_ramp')
                    {
                        return 'off';
                    }
                    return 'on';
                }));
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

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($activationRequest);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertFalse($merchant->isInternational());

        $this->assertNull($merchant->convertOnApi());
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

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

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

        $this->assertFalse($merchant->isInternational());

        $this->assertNull($merchant->convertOnApi());
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

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

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

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '1cXSLlUU8V9sXl',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testBankDetailsVerificationStatusForUnRegisteredBusiness()
    {
        $this->setUpRazorxMock();

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',
                                                  [
                                                      'business_type'           => 2,
                                                      'poi_verification_status' => 'verified',
                                                  ]);

        $merchantId = $merchantDetail['merchant_id'];

        $this->createMerchantDocumentEntries($merchantId, 'aadhar_front');
        $this->createMerchantDocumentEntries($merchantId, 'aadhar_back');

        $plan = $this->createZeroFundAccountValidationPricingPlan();

        $this->createBalanceForSharedMerchant();

        $this->fixtures->merchant->editEntity('merchant',
                                              '100000Razorpay',
                                              [
                                                  'pricing_plan_id' => $plan->getPlanId()
                                              ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Mail::fake();

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getBankDetailsVerificationStatus(), 'verified');

    }

    public function testSuccessBankDetailsVerificationForUnregistered()
    {
        $merchantDetailAttribute = [
            'business_type'         => 2,
            'promoter_pan_name'     => 'p kumar',
            'bank_account_name'     => 'pankaj k',
            'activation_status'     => 'under_review',
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'accounting',
        ];

        $favAttribute = [
            ValidationEntity::REGISTERED_NAME => "p kumar",
        ];

        $this->verifySuccessBankDetailVerification($favAttribute, $merchantDetailAttribute, 'activated_mcc_pending');
    }

    public function testSuccessBankDetailsVerificationForPartnerShip()
    {
        $merchantDetailAttribute = [
            'business_type'     => 1,
            'business_name' => 'p kumar',
            'bank_account_name' => 'pankaj k',
        ];

        $favAttribute = [
            ValidationEntity::REGISTERED_NAME => "p kumar",
        ];

        $this->verifySuccessBankDetailVerification($favAttribute, $merchantDetailAttribute, 'under_review');
    }
    public function testSuccessBankDetailsVerificationForHUF()
    {
        $merchantDetailAttribute = [
            'business_type'     => 13,
            'business_name' => 'p kumar',
            'bank_account_name' => 'pankaj k',
        ];

        $favAttribute = [
            ValidationEntity::REGISTERED_NAME => "p kumar",
        ];

        $this->verifySuccessBankDetailVerification($favAttribute, $merchantDetailAttribute, 'under_review');
    }

    public function testSuccessBankDetailsVerificationForLLP()
    {
        $merchantDetailAttribute = [
            'business_type'     => 6,
            'business_name' => 'p kumar',
            'bank_account_name' => 'pankaj k',
        ];

        $favAttribute = [
            ValidationEntity::REGISTERED_NAME => "p kumar",
        ];

        $this->verifySuccessBankDetailVerification($favAttribute, $merchantDetailAttribute, 'under_review');
    }

    public function testSuccessBankDetailsVerificationForPrivateLtd()
    {
        $merchantDetailAttribute = [
            'business_type'     => 4,
            'bank_account_name' => 'pankaj k',
            'business_name' => 'p kumar',
        ];

        $favAttribute = [
            ValidationEntity::REGISTERED_NAME => "p kumar",
        ];

        $this->verifySuccessBankDetailVerification($favAttribute, $merchantDetailAttribute, 'under_review');
    }

    public function testSuccessBankDetailsVerificationForPublicLtd()
    {
        $merchantDetailAttribute = [
            'business_type'     => 5,
            'bank_account_name' => 'pankaj k',
            'business_name' => 'p kumar',
        ];

        $favAttribute = [
            ValidationEntity::REGISTERED_NAME => "p kumar",
        ];

        $this->verifySuccessBankDetailVerification($favAttribute, $merchantDetailAttribute, 'under_review');
    }

    public function testSuccessJumbledBankDetailsVerification()
    {
        $merchantDetailAttribute = [
            'business_type'         => 2,
            'promoter_pan_name'     => 'mr subramaniam laxmi vijay',
            'bank_account_name'     => 'mr subramaniam laxmi vijay',
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'accounting',
        ];

        $favAttribute = [
            ValidationEntity::REGISTERED_NAME => "vijay laxmi subramaniam",
        ];

        $this->verifySuccessBankDetailVerification($favAttribute, $merchantDetailAttribute, 'activated_mcc_pending');
    }

    public function testProcessFavFromQueueActivatedMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'bank_details_verification_status' => 'initiated',
            'activation_status'                => 'activated',
            'fund_account_validation_id'       => 'favid123456789'
        ]);

        $this->fixtures->create('fund_account_validation', [
            ValidationEntity::ACCOUNT_STATUS => "active",
            ValidationEntity::NOTES          => [
                ValidationEntity::MERCHANT_ID => $merchantDetail['merchant_id'],
            ],
        ]);

        $fav = $this->getLastEntity('fund_account_validation', true, 'test');

        FundAccountValidation::dispatch('test', $fav['id']);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantDetail['merchant_id']);

        $this->assertEquals('failed', $merchantDetail->getBankDetailsVerificationStatus());
        $this->assertEquals('favid123456789', $merchantDetail->getFundAccountValidationId());
    }

    public function testPennyTestingFailedStatusAfterRetryAttemptCompletion()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',
                                                  [
                                                      'bank_details_verification_status' => 'initiated',
                                                      'penny_testing_updated_at'         => time() - 7300]);
        $this->ba->cronAuth();

        $this->updateRetryCountInRedis($merchantDetail, 2);

        $testData = $this->testData['testPennyTestingRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantDetail['merchant_id']);

        $this->assertEquals('failed', $merchantDetail->getBankDetailsVerificationStatus());
    }

    /**
     * @param array  $customFavAttributes
     * @param array  $customMerchantAttributes
     * @param string $accountStatus
     */
    private function verifySuccessBankDetailVerification(array $customFavAttributes , array $customMerchantAttributes, string $accountStatus): void
    {
        $defaultMerchantDetailsAttributes = [
            'poa_verification_status' => 'verified',
            'poi_verification_status' => 'verified',
            'submitted'               => 1,
            'submitted_at'            => now()->getTimestamp()
        ];

        $detailAttributes = array_merge($defaultMerchantDetailsAttributes, $customMerchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $detailAttributes);

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchantDetail['merchant_id']);

        $defaultFavAttribute = [
            ValidationEntity::ACCOUNT_STATUS => "active",
            ValidationEntity::NOTES          => [
                ValidationEntity::MERCHANT_ID => $merchantDetail['merchant_id'],
            ],
        ];

        $customFavAttributes = array_merge($defaultFavAttribute, $customFavAttributes);

        $this->fixtures->create('fund_account_validation', $customFavAttributes);

        $fav = $this->getLastEntity('fund_account_validation', true, 'test');

        FundAccountValidation::dispatch('test', $fav['id']);

        $merchant = $this->getDbEntityById('merchant', $merchantDetail['merchant_id']);

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals('verified', $merchantDetail->getBankDetailsVerificationStatus());

        $this->assertEquals($accountStatus, $merchantDetail->getActivationStatus());
    }

    private function createWebsitePolicyAndNegativeKeywordFixtures($merchantId)
    {
        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantId,
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);
    }

    public function testFailureBankDetailsVerificationForUnRegisteredBusiness()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 2, 'promoter_pan_name' => 'pankaj kumar','activation_status' => 'under_review',];

        $this->verifyFailureBankDetailsVerification($merchantAttributes);
    }

    public function testFailureBankDetailsVerificationForNameMismatchCaseForUnRegisteredBusiness()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 2, 'promoter_pan_name' => 'pankaj kumar'];

        $this->verifyFailureBankDetailsVerificationForNameMismatchCase($merchantAttributes);
    }

    public function testFailureBankDetailsVerificationForPartnerShip()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 1, 'promoter_pan_name' => 'pankaj kumar'];

        $this->verifyFailureBankDetailsVerification($merchantAttributes);
    }

    public function testFailureBankDetailsVerificationForHUF()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 13, 'promoter_pan_name' => 'pankaj kumar'];

        $this->verifyFailureBankDetailsVerification($merchantAttributes);
    }

    public function testFailureBankDetailsVerificationForNameMismatchCasePartnerShip()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 1, 'promoter_pan_name' => 'pankaj kumar'];

        $this->verifyFailureBankDetailsVerificationForNameMismatchCase($merchantAttributes);
    }

    public function testFailureBankDetailsVerificationForNameMismatchCaseHUF()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 13, 'promoter_pan_name' => 'pankaj kumar'];

        $this->verifyFailureBankDetailsVerificationForNameMismatchCase($merchantAttributes);
    }

    public function testFailureBankDetailsVerificationForRegisteredBusiness()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 4, 'business_name' => 'pankaj kumar'];

        $this->verifyFailureBankDetailsVerification($merchantAttributes);
    }

    public function testFailureBankDetailsVerificationForNameMismatchCaseForRegisteredBusiness()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantAttributes = ['business_type' => 4, 'business_name' => 'pankaj kumar'];

        $this->verifyFailureBankDetailsVerificationForNameMismatchCase($merchantAttributes);
    }

    private function verifyFailureBankDetailsVerification(array $merchantAttributes)
    {
        $defaultValues = [
            'kyc_clarification_reasons' => $this->getClarificationReason(),
        ];

        $attributes = array_merge($defaultValues, $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $attributes);

        $this->updateRetryCountInRedis($merchantDetail, 2);

        $attribute = $this->getFavAttributes($merchantDetail, "pankaj kumar", "invalid");

        $this->validateBankDetailFailureCase($attribute, $merchantDetail, 'incorrect_details');

        $this->assertPennyTestingAttemptCount($merchantDetail->getId(), 2);
    }

    public function testBankDetailsVerificationSuccessfulInRetry()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
                                                   'business_type'           => 2,
                                                   'promoter_pan_name'       => 'rishabh acharya',
                                                   'bank_account_name'       => 'rishabh acharya',
                                                   'poa_verification_status' => 'verified',
                                                   'poi_verification_status' => 'verified',
                                                   'submitted'               => 1,
                                                   'submitted_at'            => now()->getTimestamp(),
                                                   'business_category'       => 'financial_services',
                                                   'business_subcategory'    => 'accounting'
        ]);

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchantDetail['merchant_id']);

        $attribute = $this->getFavAttributes($merchantDetail, "UNREGISTERED", "active");

        $attribute1 = $this->getFavAttributes($merchantDetail, "rishabh acharya", "active");

        $this->updateRetryCountInRedis($merchantDetail);

        $this->checkFirstPennyTestingTry($attribute, $merchantDetail, 'initiated');

        $this->checkSecondPennyTestingTry($attribute1, $merchantDetail, 'activated_mcc_pending', 'verified');

        $this->assertPennyTestingAttemptCount($merchantDetail->getId(), 2);
    }

    public function testPennyTestingCronSuccessful()
    {
        $attributes = [
            'business_type'                    => 2,
            'promoter_pan_name'                => 'rishabh acharya',
            'bank_account_name'                => 'rishabh acharya',
            'poa_verification_status'          => 'verified',
            'poi_verification_status'          => 'verified',
            'bank_details_verification_status' => 'initiated',
            'penny_testing_updated_at'         => time() - 7300,
            'submitted'                        => 1,
            'submitted_at'                     => now()->getTimestamp(),
            'business_category'                => 'financial_services',
            'business_subcategory'             => 'accounting',
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $attributes);

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchantDetail['merchant_id']);

        $this->ba->cronAuth();

        $attribute = $this->getFavAttributes($merchantDetail, "rishabh acharya", "active");

        $this->updateRetryCountInRedis($merchantDetail);

        $testData = $this->testData['testPennyTestingRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantDetail['merchant_id']);

        $this->checkSecondPennyTestingTry($attribute, $merchantDetail, 'activated_mcc_pending', 'verified');
    }

    public function testPennyTestingCronActivatedMerchants()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'bank_details_verification_status' => 'initiated',
            'activation_status'                => 'activated',
            'penny_testing_updated_at'         => time() - 7300,
        ]);

        $this->ba->cronAuth();

        $testData = $this->testData['testPennyTestingRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantDetail['merchant_id']);

        $this->assertEquals($merchantDetail->getBankDetailsVerificationStatus(),'failed');
    }

    public function testStoreLegalDocumentsRetryCronFailedWitNoValidMerchant()
    {

        Config::set('services.bvs.mock', true);

        $this->fixtures->create('merchant_detail',
                                [
                                    'promoter_pan_name' => 'sdfds',
                                    'merchant_id'       => '10000000000000',
                                    'business_name'     => 'sdfds',
                                    'contact_mobile'    => '12345566'
                                ]);

        $this->fixtures->create('merchant_consent_details');

        $this->fixtures->create('merchant_consents');

        $this->ba->cronAuth();

        $testData = $this->testData['testStoreLegalDocumentsRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantConsent = $this->getLastEntity('merchant_consents', true);

        $this->assertEquals('failed', $merchantConsent['status']);

    }

    public function testStoreLegalDocumentsRetryCronFailedWithValidMerchant()
    {
        Config::set('services.bvs.mock', true);

        $this->fixtures->create('merchant_detail',
                                [
                                    'promoter_pan_name' => 'sdfds',
                                    'merchant_id'       => '10000000000000',
                                    'business_name'     => 'sdfds',
                                    'contact_mobile'    => '12345566'
                                ]);

        $this->fixtures->create('merchant_consent_details',
                                [
                                    'id' => 'KdwZeHbUYIqVnW'
                                ]);

        $this->fixtures->create('merchant_consents',
                                [
                                    'details_id' => 'KdwZeHbUYIqVnW'
                                ]);

        $this->ba->cronAuth();

        $testData = $this->testData['testStoreLegalDocumentsRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantConsent = $this->getLastEntity('merchant_consents', true);

        $this->assertEquals('initiated', $merchantConsent['status']);

    }

    public function testStoreLegalDocumentsRetryCronSuccess()
    {

        $this->fixtures->create('merchant_consents', [
            'status' => 'success'
        ]);

        $this->ba->cronAuth();

        $testData = $this->testData['testStoreLegalDocumentsRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantConsent = $this->getLastEntity('merchant_consents', true);

        $this->assertEquals('success', $merchantConsent['status']);

    }

    public function testStoreLegalDocumentsRetryCronSuccessForX()
    {

        $this->fixtures->create('merchant_consents', [
            'id'            => 'KdSCny9TA9OrmL',
            'status'        => 'success',
            'consent_for'   => 'X_Terms and Conditions'
        ]);

        $this->ba->cronAuth();

        $testData = $this->testData['testStoreLegalDocumentsRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantConsent = $this->getLastEntity('merchant_consents', true);

        $this->assertEquals('success', $merchantConsent['status']);

    }

    public function testStoreLegalDocumentsRetryCountExceeded()
    {

        $this->fixtures->create('merchant_consents', [
            'retry_count' => '3'
        ]);

        $this->ba->cronAuth();

        $testData = $this->testData['testStoreLegalDocumentsRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantConsent = $this->getLastEntity('merchant_consents', true);

        $this->assertEquals('failed', $merchantConsent['status']);

    }

    public function testStoreLegalDocumentsRetryCountExceededForX()
    {
        $this->fixtures->create('merchant_consents', [
            'id'            => 'KdSCny9TA9OrmL',
            'retry_count'   => '3',
            'consent_for'   => 'X_Terms and Conditions'
        ]);

        $this->ba->cronAuth();

        $testData = $this->testData['testStoreLegalDocumentsRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantConsent = $this->getLastEntity('merchant_consents', true);

        $this->assertEquals('failed', $merchantConsent['status']);

    }

    public function testPennyTestingCronFailure()
    {
        $this->markTestSkipped('Old way of bank account validation not used anymore');

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',
                                                  ['business_type'                    => 2,
                                                   'promoter_pan_name'                => 'rishabh acharya',
                                                   'bank_account_name'                => 'rishabh acharya',
                                                   'poa_verification_status'          => 'verified',
                                                   'poi_verification_status'          => 'verified',
                                                   'bank_details_verification_status' => 'initiated',
                                                   'penny_testing_updated_at'         => time() - 7300,
                                                   'kyc_clarification_reasons'        => $this->getClarificationReason(),
                                                   'submitted'                        => 1,
                                                   'submitted_at'                     => now()->getTimestamp()]);

        $this->ba->cronAuth();

        Mail::fake();

        $attribute = $this->getFavAttributes($merchantDetail, "random name", "active");

        $this->updateRetryCountInRedis($merchantDetail);

        $testData = $this->testData['testPennyTestingRetryCron'];

        $this->runRequestResponseFlow($testData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantDetail['merchant_id']);

        $this->checkSecondPennyTestingTry($attribute, $merchantDetail, 'needs_clarification', 'not_matched');

        $this->assertEquals($merchantDetail->getKycClarificationReasons(), $this->getClarificationReasonsForPennyTestingFailure());

        Mail::assertQueued(NeedsClarificationEmail::class);
    }

    protected function getFavAttributes($merchantDetail, string $registeredName, string $accountStatus)
    {
        return [
            ValidationEntity::REGISTERED_NAME => $registeredName,
            ValidationEntity::ACCOUNT_STATUS  => $accountStatus,
            ValidationEntity::NOTES           => [
                ValidationEntity::MERCHANT_ID => $merchantDetail['merchant_id'],
            ],
        ];
    }

    protected function checkFirstPennyTestingTry(array $attribute, &$merchantDetail, string $activationStatus)
    {
        $this->fixtures->create('fund_account_validation', $attribute);

        $fav = $this->getLastEntity('fund_account_validation', true, 'test');

        FundAccountValidation::dispatch('test', $fav['id']);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantDetail['merchant_id']);

        $this->assertEquals($merchantDetail->getBankDetailsVerificationStatus(), $activationStatus);
    }

    protected function checkSecondPennyTestingTry(array $favAttribute, &$merchantDetail, string $activationStatus, string $bankDetailsVerificationStatus)
    {
        //fund_account_validation_id after penny testing retry
        $this->fixtures->on('test')->edit('fund_account_validation', $merchantDetail['fund_account_validation_id'], $favAttribute);

        $favRetry = $this->getLastEntity('fund_account_validation', true, 'test');

        $this->assertEquals($favRetry['id'], 'fav_' . $merchantDetail['fund_account_validation_id']);

        FundAccountValidation::dispatch('test', $favRetry['id']);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchantDetail['merchant_id']);

        $this->assertEquals($bankDetailsVerificationStatus, $merchantDetail->getBankDetailsVerificationStatus());

        $this->assertEquals($activationStatus, $merchantDetail->getActivationStatus());
    }

    public function verifyFailureBankDetailsVerificationForNameMismatchCase(array $merchantAttributes)
    {
        $defaultValues = [
            'poa_verification_status'   => 'verified',
            'submitted'                 => 1,
            'kyc_clarification_reasons' => $this->getClarificationReason(),
            'submitted_at'              => now()->getTimestamp()
        ];

        $attributes = array_merge($defaultValues, $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $attributes);

        $attribute = $this->getFavAttributes($merchantDetail, "random name", "active");

        $this->validateBankDetailFailureCase($attribute, $merchantDetail, 'not_matched');
    }

    /**
     * @param array  $attribute
     * @param        $merchantDetail
     * @param string $verificationStatus
     */
    protected function validateBankDetailFailureCase(array $attribute, $merchantDetail, string $verificationStatus): void
    {
        Mail::fake();

        $this->mockRaven();

        $this->fixtures->create('fund_account_validation', $attribute);

        $fav = $this->getLastEntity('fund_account_validation', true, 'test');

        FundAccountValidation::dispatch('test', $fav['id']);

        $merchant = $this->getDbEntityById('merchant', $merchantDetail['merchant_id']);

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals($merchantDetail->getBankDetailsVerificationStatus(), $verificationStatus);

        $this->assertEquals($merchantDetail->getActivationStatus(), 'needs_clarification');

        $this->assertEquals($merchantDetail->getKycClarificationReasons(), $this->getClarificationReasonsForPennyTestingFailure());

        Mail::assertQueued(NeedsClarificationEmail::class);
    }

    protected function createBalanceForSharedMerchant()
    {
        $balanceData = [
            'id'          => '100abc000abc00',
            'merchant_id' => '100000Razorpay',
            'type'        => 'primary',
            'currency'    => 'INR',
            'balance'     => 500,
        ];
        $this->fixtures->create('balance', $balanceData);
    }

    protected function getClarificationReasonsForPennyTestingFailure()
    {
        return [
            Entity::ADDITIONAL_DETAILS => [
                "address_proof_url" => [[
                                            'reason_type' => 'predefined',
                                            'field_type'  => 'document',
                                            'reason_code' => 'unable_to_validate_acc_number',
                                        ]],
                "cancelled_cheque"  => [[
                                            'reason_type' => 'predefined',
                                            'field_type'  => 'document',
                                            'reason_code' => 'unable_to_validate_acc_number',
                                        ]],
            ],
        ];
    }

    protected function createMerchantUserMapping(string $userId, string $merchantId, string $role, $mode = 'test')
    {
        DB::connection($mode)->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }

    protected function getClarificationReason()
    {
        return [
            Entity::ADDITIONAL_DETAILS => [
                "address_proof_url" => [[
                                            'reason_type' => 'predefined',
                                            'field_type'  => 'document',
                                            'reason_code' => 'unable_to_validate_acc_number',
                                        ]],
            ],
        ];
    }

    /**
     * @param $attribute
     * @param $merchantDetail
     */
    public function testValidateNeedsClarificationStatusChange(): void
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',
                                                  ['business_type'                    => 1,
                                                   'promoter_pan_name'                => 'pankaj kumar',
                                                   'poa_verification_status'          => 'verified',
                                                   'bank_details_verification_status' => 'verified',
                                                   'submitted'                        => 1,
                                                   'activation_status'                => 'needs_clarification',
                                                   'submitted_at'                     => now()->getTimestamp()]);
        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                        'address_proof_url',
                                        'business_pan_url',
                                        'business_proof_url',
                                        'promoter_address_url'
                                    ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID], $merchantUser['id']);

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

    protected function createZeroFundAccountValidationPricingPlan()
    {
        $pricingPlan = [
            'plan_name'      => 'Zero pricing plan',
            'percent_rate'   => 0,
            'fixed_rate'     => 0,
            "min_fee"        => 0,
            'org_id'         => '100000razorpay',
            'type'           => 'pricing',
            'product'        => 'primary',
            "feature"        => 'fund_account_validation',
            'payment_method' => 'bank_account',
        ];

        return $this->fixtures->create('pricing', $pricingPlan);
    }

    /**
     * @param     $merchantDetail
     * @param int $count
     */
    private function updateRetryCountInRedis($merchantDetail, int $count = 1): void
    {
        $pennyTestingAttemptRedisKey = (new PennyTesting())->getPennyTestingAttemptRedisKey($merchantDetail->getId());

        $this->app['cache']->put($pennyTestingAttemptRedisKey, $count, DetailConstants::PENNY_TESTING_ATTEMPT_COUNT_TTL_IN_SEC);
    }

    private function assertPennyTestingAttemptCount(string $merchantId, int $count)
    {
        $pennyTestingAttemptRedisKey = (new PennyTesting())->getPennyTestingAttemptRedisKey($merchantId);

        $actualCount = $this->app['cache']->get($pennyTestingAttemptRedisKey);

        $this->assertEquals($count, $actualCount);
    }

    private function createDocumentEntities(string $merchantId, array $documentTypes)
    {
        $data = [
            'document_types' => $documentTypes,
            'attributes'     => [
                'merchant_id'   => $merchantId,
                'file_store_id' => 'abcdefgh12345',]
        ];

        $this->fixtures->create('merchant_document:multiple', $data);
    }

    public function cinVerification($test, string $mockStatus, $cinVerificationStatus, array $input): void
    {
        Config::set('applications.kyc.cin_authentication', $mockStatus);

        Config::set('applications.kyc.mock', true);

        $this->fixtures->on('live')->edit('merchant_detail', '10000000000000', $input);
        $this->fixtures->on('test')->edit('merchant_detail', '10000000000000', $input);

        $this->ba->proxyAuth();

        $testData = $this->testData[$test];

        $this->startTest($testData);

        $merchant = $this->getDbEntityById('merchant', 10000000000000);

        $merchantDetail = $merchant->merchantDetail;

        $this->assertEquals($merchantDetail->getCinVerificationStatus(), $cinVerificationStatus);
    }

    protected function mockRaven()
    {
        $raven = Mockery::mock('RZP\Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')
              ->with(Mockery::type('string'), 'post', Mockery::type('array'))
              ->andReturnUsing(function ($route, $method, $input)
              {
                  $this->mockedRavenRequest = [$route, $method, $input];

                  $response = [
                      'success' => true,
                  ];

                  return $response;
              })->between(1, 10);

        $this->app->instance('raven', $raven);
    }

    // whenever merchant submits action form/kyc form, we need to call Terminals Service to process instrument requests related
    // information. This test asserts that that Terminals Service is called with the right parameters
    public function testInternalInstrumentStatusUpdateRequestedOnMerchantActivationFormSubmission()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $receivedMethod = '';

        $receivedContent = '';

        $receivedPath = '';

        // cannot assert within mock as exception failures thrown are handled in code somewhere else, leading to silent failure of assertions failures
        $this->mockTerminalsServiceSendRequest(function($path, $content, $method) use (&$receivedPath, &$receivedContent, &$receivedMethod) {

            $receivedPath = $path;

            $receivedContent = $content;

            $receivedMethod = $method;
        }, 1);

        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '1cXSLlUU8V9sXl',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);;

        $this->startTest();

        $this->assertEquals('PATCH', $receivedMethod);

        $this->assertEquals('v2/internal_instrument_request?status=action_required&merchant_ids=1cXSLlUU8V9sXl', $receivedPath);

        $this->assertEquals('{"status":"requested"}', $receivedContent);
    }

    public function testAddingVirtualAccountInLinkedAccount()
    {
        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $merchantId = $linkedAccount->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testAadhaarNotLinkedWithoutStakeholderEntity()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testAadhaarNotLinkedWithStakeholderEntity()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->create('stakeholder', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testHardLimitReachedWithLevelThree()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->fixtures->create('merchant_auto_kyc_escalations', [
            'merchant_id'       =>  $merchantId,
            'escalation_level'  =>  4,
            'escalation_type'   =>  'hard_limit'
        ]);

        $response = $this->startTest();

    }

    public function testActivationFormSubmissionOfLinkedAccount()
    {
        $core = new DetailCore();

        $linkedAccount = $this->fixtures->create('merchant', ['parent_id' => '10000000000000']);

        $merchantId = $linkedAccount->getId();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting'
        ]);

        $this->assertEquals(true, $core->canSubmitActivationForm($merchantDetails, $linkedAccount, []));
    }

    public function testHardLimitNotReached()
    {

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

    }

    public function testHardLimitEmailSent()
    {
        $this->markTestSkipped('Emails will be not be sent at this level due to change in threshold limit');

        Mail::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $data = [
            'activation_status' => 'activated_mcc_pending',
            'business_website'           => null
        ];

        $this->fixtures->on('live')->edit('merchant_detail', $merchantId, $data);
        $this->fixtures->on('test')->edit('merchant_detail', $merchantId, $data);

        $createdAt = Carbon::now()->subDays(5)->getTimestamp();

        $this->fixtures->on('live')->create('merchant_auto_kyc_escalations', [
            'merchant_id'      => $merchantId,
            'escalation_level' => 3,
            'escalation_type'  => 'hard_limit',
            'created_at'       => $createdAt,
        ]);
        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'        => 'payment',
            'amount'      => 1500000,   // in paisa
            'merchant_id' => $merchantId
        ]);
        $this->ba->cronAuth('live');

        $testData = [
            'url'     => '/merchants/auto-kyc-cron/escalations',
            'method'  => 'post',
            'content' => [

            ],
        ];

        $this->makeRequestAndGetContent($testData);

        //verify email has been sent
        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.funds_on_hold', $mail->getTemplate());

            return true;
        });

    }

    public function testHardLimitEmailNotSent()
    {
        Mail::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $data = [
            'activation_status'     => 'activated_mcc_pending'
        ];

        $this->fixtures->on('live')->edit('merchant_detail', $merchantId, $data);
        $this->fixtures->on('test')->edit('merchant_detail', $merchantId, $data);

        $createdAt = Carbon::now()->subDays(5)->getTimestamp();

        $this->fixtures->on('live')->create('merchant_auto_kyc_escalations', [
            'merchant_id'       =>  $merchantId,
            'escalation_level'  =>  3,
            'escalation_type'   =>  'hard_limit',
            'created_at'     => $createdAt,
        ]);
        $this->ba->cronAuth('live');

        $testData = [
            'url'     => '/merchants/auto-kyc-cron/escalations',
            'method'  => 'post',
            'content' => [

            ],
        ];

        $this->fixtures->create('merchant', [
            'id'     => '10000000000040',
            'email'  => 'test@razorpay.com',
        ]);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => '1cXSLlUU8V9sXl']);

        $this->fixtures->create('feature', [
            'name'          => FeatureConstants::SKIP_SUBM_ONBOARDING_COMM,
            'entity_id'     => '10000000000000',
            'entity_type'   => 'merchant',
        ]);

        $this->makeRequestAndGetContent($testData);

        Mail::assertNotQueued(\RZP\Mail\Merchant\MerchantOnboardingEmail::class);
    }

    public function testKycSubmissionForInstantlyActivatedMerchantWithL2Milestone()
    {
        Mail::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        Mail::assertQueued(NotifyActivationSubmission::class);
    }

    public function testMerchantActivationWithEmptyBusinessCategoryInX()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptyBusinessCategoryInPg()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptyIfscInX()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptyIfscInPg()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptySubCategoryInX()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptySubCategoryInPg()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptyCompanyPanInX()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptyCompanyPanInPg()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptyBusinessRegisteredAddressInX()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationWithEmptyBusinessRegisteredAddressInPg()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' .self::DEFAULT_MERCHANT_ID);

        $this->startTest();
    }

    public function testMerchantActivationOtpSendBanking()
    {
        $smsPayload = [
            'success'    => true,
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => '10000000000000:10000000000000:x_verify_email:MOCK_TOKEN1234',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')->with([
            'receiver'  => 'hello123@c.com',
            'context'   => '10000000000000:10000000000000:x_verify_email:MOCK_TOKEN1234',
            'source'    => 'api',
            'expires_at' => 20
        ])->willReturn($smsPayload);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
            'id' => '10000000000000',
            'email' => null,
        ]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'activated'
            ]);

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => true, 'business_banking' => true,'email' => null, 'signup_source' => 'banking']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());
        $this->ba->addXOriginHeader();

        $response = $this->startTest();
        $this->assertNotEmpty($response['token']);
    }


    public function testMerchantActivationOtpSendPrimary()
    {
        $smsPayload = [
            'success'    => true,
            'otp'        => '0007',
            'expires_at' => Carbon::now()->addMinutes(30)->timestamp,
            'context'    => '10000000000000:10000000000000:verify_email:MOCK_TOKEN1234',
        ];

        $ravenMock = $this->getMockBuilder(Raven::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['generateOtp'])
            ->getMock();

        $this->app->instance('raven', $ravenMock);

        $this->app['raven']->method('generateOtp')->with([
            'receiver'  => 'hello123@c.com',
            'context'   => '10000000000000:10000000000000:verify_email:MOCK_TOKEN1234',
            'source'    => 'api',
            'expires_at' => 20
        ])->willReturn($smsPayload);

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
            'id' => '10000000000000',
            'email' => null,
        ]);

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => '10000000000000',
                'activation_status' => 'activated'
            ]);

        $this->fixtures->edit('merchant',
            '10000000000000',
            ['activated' => true, 'business_banking' => false,'email' => null, 'signup_source' => 'primary']);
        $this->ba->proxyAuth('rzp_test_' . '10000000000000', $user['id']);
        $response = $this->startTest();
        $this->assertNotEmpty($response['token']);

    }

    protected function getExpectedArraysForWorkflowObserverTestCases($arrayType) : array
    {
        if ($arrayType === self::MERCHANT_ACTIVATED_WORKFLOW_DATA)
        {
            return [
                'entity_name'   => "merchant_detail",
                'workflow'      => [
                    'name'      => "Activation Workflow",
                ],
                'permission'    =>[
                    'name'  => "edit_activate_merchant"
                ],
                'state'         =>"open",
                'maker_type'    => "admin",
                'org_id'        => "org_100000razorpay",
                'approved'      =>  FALSE,
                'current_level' =>  1,
            ];
        }

        if ($arrayType === self::MERCHANT_ACTIVATED_ES_DATA)
        {
            return [
                'method' => "PATCH",
                'payload' => [

                ],
                'workflow_observer_data' =>  [
                    'ticket_id' => "123",
                    'fd_instance' => "rzpind"
                ],
                'state' => "open",
                'route' => "merchant_activation_status",
            ];
        }
    }

    public function testInternalMerchantGetRejectionReasonsWithRejectionOptionDisableSettlement()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->makeRequestAndGetContent([
            'method' => 'PATCH',
            'url' => '/merchant/activation/10000000000000/activation_status',
            'content' => [
                'activation_status' => 'rejected',
                'rejection_reasons' => [
                    [
                        'reason_category' => 'risk_related_rejections',
                        'reason_code' => 'reject_on_risk_remarks',
                    ],
                ],
                'rejection_option' => 'disable_settlement',
            ],
        ]);

        $this->ba->careAppAuth();

        $this->startTest();

        $merchant = $this->getEntityById('Merchant', '10000000000000', true);

        $this->assertFalse($merchant['live']);

        $this->assertTrue($merchant['hold_funds']);

    }

    public function testInternalMerchantGetRejectionReasonsWithRejectionOptionEnableSettlement()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->makeRequestAndGetContent([
            'method' => 'PATCH',
            'url' => '/merchant/activation/10000000000000/activation_status',
            'content' => [
                'activation_status' => 'rejected',
                'rejection_reasons' => [
                    [
                        'reason_category' => 'risk_related_rejections',
                        'reason_code' => 'reject_on_risk_remarks',
                    ],
                ],
                'rejection_option' => 'enable_settlement',
            ],
        ]);

        $this->ba->careAppAuth();

        $this->startTest();

        $merchant = $this->getEntityById('Merchant', '10000000000000', true);

        $this->assertFalse($merchant['live']);

    }

    public function testInternalMerchantGetRejectionReasonsWithRejectionOptionProofOfDeliveryMail()
    {
        Mail::fake();

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => '10000000000000',
        ]);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->makeRequestAndGetContent([
            'method' => 'PATCH',
            'url' => '/merchant/activation/10000000000000/activation_status',
            'content' => [
                'activation_status' => 'rejected',
                'rejection_reasons' => [
                    [
                        'reason_category' => 'risk_related_rejections',
                        'reason_code' => 'reject_on_risk_remarks',
                    ],
                ],
                'rejection_option' => 'proof_of_delivery_mail',
            ],
        ]);

        $this->ba->careAppAuth();

        $this->startTest();

        $merchant = $this->getEntityById('Merchant', '10000000000000', true);

        $this->assertFalse($merchant['live']);

        $this->assertTrue($merchant['hold_funds']);

        Mail::assertQueued(RejectionSettlement::class, function ($mail)
        {
            $this->assertEquals('emails/merchant/settlement_rejection_mail', $mail->view);

            return true;
        });
    }

    public function testPushSegmentEventsAndAutomaticFlagAdditionWhenMerchantActivatedOnInternational()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
            ->method('pushIdentifyAndTrackEvent')
            ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                $this->assertNotNull($properties);
                $this->assertTrue(in_array($eventName, ["International Payments Enabled"], true));
            }));

        (new Detail\Core())->getMerchantAndSetBasicAuth($merchantId);

        (new Detail\InternationalCore())->activateInternational($merchant);

        $this->assertTrue($this->fixtures->merchant->isFeatureEnabled([FeatureConstants::ENABLE_3DS2], $merchantId));

        $this->assertTrue($merchant->isInternational());

        $this->assertFalse($merchant->convertOnApi());
    }

    public function testStorageConsentForInstantlyActivatedMerchantWithL2Milestone()
    {
        Mail::fake();

        Config::set('services.bvs.mock', true);

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $consentDetail = $this->getDbLastEntity('merchant_consents', 'test');

        $this->assertEquals($merchantId, $consentDetail['merchant_id']);

        $this->assertEquals('initiated', $consentDetail['status']);
    }


    public function testConsentDetailsForTnCValidation()
    {
        Mail::fake();

        Config::set('services.bvs.mock', true);

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $merchantConsents = \DB::connection('test')->select("select * from merchant_consents where merchant_id = '$merchantId'ORDER BY created_at DESC LIMIT 3 ");

        $values = ["L2_Privacy Policy","L2_Service Agreement","L2_Terms and Conditions"];

        $expectedConsents = [];

        foreach ($merchantConsents as $consent) {
            $expectedConsents[] = $consent->consent_for;
        }

        $this->assertCount(3, $expectedConsents);
        $this->assertEmpty(array_diff($values, $expectedConsents));

    }

    public function testStorageConsentNullForMerchantWithL2Milestone()
    {
        Mail::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->startTest();

        $consentDetail = $this->getDbLastEntity('merchant_consents', 'test');

        $this->assertNull($consentDetail);
    }

    public function testKafkaSuccessForLegalDocument()
    {
        Config::set('services.bvs.mock', true);

        $merchantConsent = $this->fixtures->create('merchant_consents');

        $kafkaEventPayload = [
            'data' => [
                'id'=> 'KdRvpX6ffYF7yG',
                'status' => "success",
                'documents_details' => [
                    [
                        'type' => 'L2_Terms and Conditions',
                        'acceptance_timestamp' => "1651060634",
                        'status' => "success"
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-legal-document-result-events', $kafkaEventPayload, 'test');

        $consentDetail = $this->getDbLastEntity('merchant_consents', 'test');

        $this->assertEquals('success', $consentDetail['status']);
    }

    public function testKafkaSuccessForWebsitePolicy()
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($output);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://www.sukhdev.org'
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            'data' => [
                "website_verification_id" => "L61kGPVWKT05Qx",
                "status" => "completed",
                "verification_result" => [
                    "terms" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/termsofuse"],
                            "confidence_score" => 0.5775,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "refund" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/refundpolicy"],
                            "confidence_score" => 0.6185,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "privacy" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/privacypolicy"],
                            "confidence_score" => 0.7067197799682617,
                            "relevant_details" => [
                                "note" =>
                                    "Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,",
                            ],
                            "validation_result" => true,
                        ],
                    ],
                    "contact_us" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/contactus"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                ],
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-website-verification-notification-events', $kafkaEventPayload, 'test');

        $bvsValidation = $this->getDbLastEntity('bvs_validation');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail');

        $this->assertEquals('success', $bvsValidation['validation_status']);

        $this->assertEquals('verified', $verificationDetail['status']);
    }

    public function testKafkaFailureForWebsitePolicy()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => null
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            'data' => [
                "website_verification_id" => "L61kGPVWKT05Qx",
                "status" => "completed",
                "result" => [
                    "terms" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/termsofuse"],
                            "confidence_score" => 0.5775,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "refund" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/refundpolicy"],
                            "confidence_score" => 0.6185,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "privacy" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/privacypolicy"],
                            "confidence_score" => 0.7067197799682617,
                            "relevant_details" => [
                                "note" =>
                                    "Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,",
                            ],
                            "validation_result" => false,
                        ],
                    ],
                    "contact_us" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/contactus"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                ],
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-website-verification-notification-events', $kafkaEventPayload, 'test');

        $bvsValidation = $this->getDbLastEntity('bvs_validation');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail');

        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals('success', $bvsValidation['validation_status']);

        $this->assertEquals('failed', $verificationDetail['status']);

        $this->assertNull($merchantWebsiteDetail['admin_website_details']['website']);
    }

    public function testKafkaSuccessForLegalDocumentWithExtraFieldsInPayload()
    {
        Config::set('services.bvs.mock', true);

        $merchantConsent = $this->fixtures->create('merchant_consents');

        $kafkaEventPayload = [
            'data' => [
                'id'                => 'KdRvpX6ffYF7yG',
                'status'            => "success",
                'documents_details' => [
                    [
                        'type'                 => 'L2_Terms and Conditions',
                        'acceptance_timestamp' => "1651060634",
                        'status'               => "success"
                    ]
                ],
                'owner_id'          => "LP2f6TKmiuec10",
                'client_id'         => "API",
                'platform'          => "pg",
                'mode'              => "test"
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-legal-document-result-events', $kafkaEventPayload, 'test');

        $consentDetail = $this->getDbLastEntity('merchant_consents', 'test');

        $this->assertEquals('success', $consentDetail['status']);
    }


    public function testKafkaFailureForLegalDocument()
    {
        Config::set('services.bvs.mock', true);

        $merchantConsent = $this->fixtures->create('merchant_consents');

        $kafkaEventPayload = [
            'data' => [
                'id'=> 'KdRvpX6ffYF7yG',
                'status' => "failed",
                'documents_details' => [
                    [
                        'type' => 'Terms and Conditions',
                        'acceptance_timestamp' => "1651060634",
                        'status' => "failed"
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-legal-document-result-events', $kafkaEventPayload, 'test');

        $consentDetail = $this->getDbLastEntity('merchant_consents', 'test');

        $this->assertEquals('failed', $consentDetail['status']);
    }

    public function testKafkaResponseForMerchantConsentDetailNotpresent()
    {
        Config::set('services.bvs.mock', true);

        $this->fixtures->create('merchant_consents');

        $kafkaEventPayload = [
            'data' => [
                'id'=> 'KdRvpX1ffYF98G',
                'status' => "success",
                'documents_details' => [
                    [
                        'type' => 'Terms and Conditions',
                        'acceptance_timestamp' => "1651060634",
                        'status' => "success"
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-legal-document-result-events', $kafkaEventPayload, 'test');

        $consentDetail = $this->getDbLastEntity('merchant_consents', 'test');

        $this->assertEquals('failed', $consentDetail['status']);
    }

    public function testKafkaFailureForNegativeKeywords()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => null
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05QT',
            'artefact_type'     => 'negative_keywords',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            "data" =>[
                "id" => "L61kGPVWKT05QT",
                "status" => "success",
                "document_details" => [
                    "result" => [
                        "prohibited" => [
                            "drugs" => [
                                "Phrases" => [
                                    "cannabis" => 60,
                                    "cbd" => 1,
                                    "weed" => 1
                                ],
                                "total_count" => 62,
                                "unique_count" => 3
                            ],
                            "financial services" => [
                                "Phrases" => [
                                    "investment" => 2
                                ],
                                "total_count" => 2,
                                "unique_count" => 1
                            ],
                            "miscellaneous" => [
                                "Phrases" => [
                                    "cash" => 2,
                                    "cigarette" => 1,
                                    "cigarettes" => 2,
                                    "fast" => 8,
                                    "hazardous chemicals" => 1,
                                    "money" => 7,
                                    "payment methods" => 1,
                                    "rapid" => 1,
                                    "supplement" => 14,
                                    "supplements" => 4,
                                    "thc" => 1
                                ],
                                "total_count" => 42,
                                "unique_count" => 11
                            ],
                            "pharma" => [
                                "Phrases" => [
                                    "alcohol" => 8,
                                    "cannabinoid" => 37,
                                    "codeine" => 1,
                                    "hemp" => 1,
                                    "marijuana" => 31,
                                    "pain" => 1,
                                    "pharmaceutical" => 5,
                                    "prescription" => 20,
                                    "topical" => 19,
                                    "valium" => 1
                                ],
                                "total_count" => 124,
                                "unique_count" => 10
                            ],
                            "tobacco products" => [
                                "Phrases" => [
                                    "tobacco" => 1
                                ],
                                "total_count" => 1,
                                "unique_count" => 1
                            ],
                            "travel" => [
                                "Phrases" => [
                                    "booking" => 2,
                                    "travel" => 2
                                ],
                                "total_count" => 4,
                                "unique_count" => 2
                            ]
                        ],
                        "required" => [
                            "policy disclosure" => [
                                "Phrases" => [
                                    "cancellations" => 1,
                                    "claims" => 11,
                                    "contact us" => 1,
                                    "delivery" => 46,
                                    "payment" => 4,
                                    "payments" => 2,
                                    "privacy" => 5,
                                    "privacy policy" => 8,
                                    "refund" => 6,
                                    "refund policy" => 4,
                                    "refunds" => 1,
                                    "return" => 3,
                                    "return policy" => 2,
                                    "returns" => 3,
                                    "terms of service" => 1
                                ],
                                "total_count" => 98,
                                "unique_count" => 15
                            ],
                        ],
                    ],
                    "website_url" => "https://www.hempstrol.com"
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-kyc-document-result-events', $kafkaEventPayload, 'test');

        $bvsValidation = $this->getDbLastEntity('bvs_validation');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail');

        $this->assertEquals('success', $bvsValidation['validation_status']);

        $this->assertEquals('failed', $verificationDetail['status']);

    }

    public function testKafkaSuccessForNegativeKeywords()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => null
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05QT',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            "data" =>[
                "id" => "L61kGPVWKT05QT",
                "status" => "success",
                "document_details" => [
                    "result" => [
                        "prohibited" => [
                            "drugs" => [
                                "Phrases" => [
                                    "cannabis" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "financial services" => [
                                "Phrases" => [
                                    "investment" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "miscellaneous" => [
                                "Phrases" => [
                                    "cash" => 0,
                                    "cigarette" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "pharma" => [
                                "Phrases" => [
                                    "alcohol" => 0,
                                    "cannabinoid" => 0,
                                    "codeine" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "tobacco products" => [
                                "Phrases" => [
                                    "tobacco" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "travel" => [
                                "Phrases" => [
                                    "booking" => 0,
                                    "travel" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ]
                        ],
                        "required" => [
                            "policy disclosure" => [
                                "Phrases" => [
                                    "cancellations" => 1,
                                    "claims" => 11,
                                    "contact us" => 1,
                                    "delivery" => 46,
                                    "payment" => 4,
                                    "payments" => 2,
                                    "privacy" => 5,
                                    "privacy policy" => 8,
                                    "refund" => 6,
                                    "refund policy" => 4,
                                    "refunds" => 1,
                                    "return" => 3,
                                    "return policy" => 2,
                                    "returns" => 3,
                                    "terms of service" => 1
                                ],
                                "total_count" => 98,
                                "unique_count" => 15
                            ],
                        ],
                    ],
                    "website_url" => "https://www.hempstrol.com"
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-kyc-document-result-events', $kafkaEventPayload, 'test');

        $bvsValidation = $this->getDbLastEntity('bvs_validation');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail');

        $this->assertEquals('success', $bvsValidation['validation_status']);

        $this->assertEquals('verified', $verificationDetail['status']);

    }

    public function testKafkaNotCompletedForNegativeKeywords()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => null
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05QT',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            "data" =>[
                "id" => "L61kGPVWKT05QT",
                "status" => "failed",
                "document_details" => [
                    "website_url" => "https://www.hempstrol.com"
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-kyc-document-result-events', $kafkaEventPayload, 'test');

        $bvsValidation = $this->getDbLastEntity('bvs_validation');

        $verificationDetail = $this->getDbLastEntity('merchant_verification_detail');

        $this->assertEquals('failed', $bvsValidation['validation_status']);

        $this->assertEquals('failed', $verificationDetail['status']);

    }


    public function testSubmitPartnerKycFromMerchantKycFormForRiskyMerchant()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->setupKycSubmissionForInstantlyActivatedMerchant($merchantId);

        $this->fixtures->merchant->editEntity('merchant', $merchantId, ['partner_type' => MerchantConstants::AGGREGATOR]);

        $this->setupMerchantDetailVerificationStatus($merchantId);

        $this->mockMerchantImpersonated($merchantId);

        $this->setupWorkflow('Activate partner', 'edit_activate_partner');
        $this->setupImpersonatedWorkflow();

        $this->startTest();

        $testData = $this->testData['submitKyc'];

        $testData['response']['content']['activated'] = 0;

        $this->startTest($testData);

        $partnerActivation = $this->getDbEntityById('partner_activation', $merchantId);

        $this->assertEquals($partnerActivation->getActivationStatus(), null);

        $workflowActions = $this->getDbEntities('workflow_action');

        $this->assertEquals(2, count($workflowActions));

        $this->assertEquals('merchant_detail', $workflowActions->get(0)['entity_name']);
        $this->assertEquals('open', $workflowActions->get(0)['state']);

        $this->assertEquals('partner_activation', $workflowActions->get(1)['entity_name']);
        $this->assertEquals('open', $workflowActions->get(1)['state']);
    }

    private function setupMerchantDetailVerificationStatus($merchantId) {
        $otherMerchantDetailAttributes = [
            'gstin'                            => null,
            'business_type'                    => 1,
            'bank_account_number'              => '123456789012345',
            'bank_branch_ifsc'                 => 'ICIC0000001',
            'poi_verification_status'          => 'verified',
            'gstin_verification_status'        => null,
            'bank_details_verification_status' => 'verified',
        ];

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $otherMerchantDetailAttributes);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $otherMerchantDetailAttributes);

        $defaultStakeholderAttributes = [
            'merchant_id'                          => $merchantId,
            'aadhaar_linked'                       => 1,
            'aadhaar_esign_status'                 => 'verified',
            'aadhaar_verification_with_pan_status' => 'verified'
        ];

        $this->fixtures->create('stakeholder', $defaultStakeholderAttributes);
    }

    private function setupImpersonatedWorkflow() {
        $permission = $this->fixtures->create('permission', [
            'name' => 'impersonating_merchant_dedupe',
            'category' => 'impersonating_merchant_dedupe',
            'description' => 'impersonating_merchant_dedupe',
            'assignable' => true
        ]);

        $permissionMapData = [
            'permission_id'   => $permission->getId(),
            'entity_id'       => Org::RZP_ORG,
            'entity_type'     => 'org',
            'enable_workflow' => true
        ];

        DB::connection('test')->table('permission_map')->insert($permissionMapData);
        DB::connection('live')->table('permission_map')->insert($permissionMapData);

        $workflow = $this->fixtures->create('workflow', [
            'name'   => 'impersonating_merchant_dedupe',
            'org_id' => Org::RZP_ORG,
        ]);

        $workflow->permissions()->attach($permission);
    }

    private function mockMerchantImpersonated($merchantId) {
        $mockedResponseForMatch[] = [
            'field'   => MerchantDetails::BUSINESS_NAME,
            'list'    => 'authorities_list',
            'score'   => 900
        ];

        $mockedResponseForDetails[] = [
            'field'     => MerchantDetails::BUSINESS_NAME,
            'list'      => 'authorities_list',
            'score'     => 900,  // some random score
            'matched_entity' => [
                [
                    'key' => 'id',
                    'value' => '10000000000'
                ]
            ],
        ];

        $merchantRiskClientMock = Mockery::mock('RZP\Services\MerchantRiskClient');

        $merchantRiskClientMock->shouldReceive('getMerchantImpersonatedDetails')
                               ->andReturn([
                                   "client_type" => "onboarding",
                                   "entity_id" => $merchantId,
                                   "fields" => $mockedResponseForDetails
                               ]);

        $merchantRiskClientMock->shouldReceive('getMerchantRiskScores')
                               ->andReturn([
                                   "client_type" => "onboarding",
                                   "entity_id" => $merchantId,
                                   "fields" => $mockedResponseForMatch
                               ]);

        $this->app->instance('merchantRiskClient', $merchantRiskClientMock);
    }

    public function testL2SegmentEventNotSentDuringNC(): void
    {
        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $eventAttributes, $eventName) {
                        if ($eventName === "KYC Form Saved")
                        {
                            $this->assertTrue(array_key_exists("business_type", $eventAttributes));
                            $this->assertTrue(array_key_exists("business_category", $eventAttributes));
                            $this->assertTrue(in_array($eventName, ["KYC Form Saved"], true));
                        }
                        else
                        {
                            if ($eventName === "Activation Status changed")
                            {
                                $this->assertTrue(array_key_exists("activation_status", $eventAttributes));
                                $this->assertTrue(array_key_exists("activated", $eventAttributes));
                                $this->assertTrue(array_key_exists("live", $eventAttributes));
                                $this->assertTrue(in_array($eventName, ["Activation Status changed"], true));
                            }
                        }
                    }));

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',
                                                  ['business_type'                    => 1,
                                                   'promoter_pan_name'                => 'pankaj kumar',
                                                   'poa_verification_status'          => 'verified',
                                                   'bank_details_verification_status' => 'verified',
                                                   'submitted'                        => 1,
                                                   'activation_status'                => 'needs_clarification',
                                                   'submitted_at'                     => now()->getTimestamp()]);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                          'address_proof_url',
                                          'business_pan_url',
                                          'business_proof_url',
                                          'promoter_address_url'
                                      ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID], $merchantUser['id']);

        $this->startTest();
    }
}
