<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Illuminate\Support\Facades\App;
use Mail;
use Config;
use Mockery;

use RZP\Constants;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Http\Request\Requests;
use RZP\Error\ErrorCode;
use RZP\Models\Base\EsDao;
use RZP\Constants\Timezone;
use RZP\Models\ClarificationDetail\Repository;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\Core;
use RZP\Models\Merchant\Cron\Jobs\NcRevampReminderCronJob;
use RZP\Models\User\Role;
use RZP\Services\DiagClient;
use RZP\Models\Merchant\Document\Type;
use RZP\Services\KafkaProducer;
use RZP\Services\KafkaProducerClient;
use RZP\Services\FreshdeskTicketClient;
use RZP\Services\RazorXClient;
use RZP\Services\HubspotClient;
use RZP\Services\SplitzService;
use RZP\Mail\Merchant\Rejection;
use Functional\Helpers\BvsTrait;
use RZP\Tests\Traits\MocksSplitz;
use Illuminate\Http\UploadedFile;
use RZP\Services\SalesForceClient;
use Illuminate\Support\Facades\Http;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\ServerErrorException;
use RZP\Services\KafkaMessageProcessor;
use RZP\Models\Merchant\Document\Source;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Mail\Merchant\MerchantDashboardEmail;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Mail\Merchant\MerchantBusinessWebsiteAdd;
use RZP\Mail\Merchant\RejectionReasonNotification;
use RZP\Models\Merchant\Detail\BusinessSubcategory;
use RZP\Mail\Merchant\MerchantBusinessWebsiteUpdate;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Merchant\Bvs\BvsValidationTest;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\Document\Entity as MerchantDocuments;
use RZP\Models\Workflow\Action\Repository as ActionRepository;
use RZP\Mail\Merchant\RazorpayX\AccountActivationConfirmation;
use RZP\Models\Admin\Permission\Repository as PermissionRepository;
use RZP\Models\Merchant\Consent\Repository as MerchantConsentRepository;
use RZP\Models\Merchant\Consent\Details\Repository as MerchantConsentDetailsRepo;


class MerchantDetailTest extends OAuthTestCase
{
    use PartnerTrait;
    use BvsTrait;
    use RazorxTrait;
    use PaymentTrait;
    use TerminalTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use WorkflowTrait;
    use MocksSplitz;
    use MocksDiagTrait;
    use CreateLegalDocumentsTrait;

    const PARTNER                = 'partner';
    const ACTIVATION             = 'activation';
    const DEACTIVATION           = 'deactivation';
    const DUMMY_APP_ID_1         = '8ckeirnw84ifke';
    const DUMMY_APP_ID_2         = '10000RandomApp';
    const DUMMY_APP_ID_3         = '11111RandomApp';
    const DEFAULT_MERCHANT_ID    = '10000000000000';
    const DEFAULT_SUBMERCHANT_ID = '10000000000009';

    protected $config;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantDetailTestData.php';

        parent::setUp();

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();

        $this->config = App::getFacadeRoot()['config'];
    }

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    protected function mockBvsService()
    {
        $mock = $this->mockCreateLegalDocument();

        $mock->expects($this->once())->method('createLegalDocument')->withAnyParameters();
    }

    public function testGetMerchantDetails()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->fixtures->on('test')->create('merchant_document', [
            'id'            => 'DM6dWd1tzUfbnM',
            'merchant_id'   => $merchant['id'],
            'document_type' => 'Address_proof_url',
            'file_store_id' => 'DM6dXJfU4WzeAF',
            'entity_type'   => 'merchant'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testGetAccountingIntegrationMerchantDetails()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->on('live')->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id' => $merchantId,
        ]);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['server']['HTTP_X-Razorpay-Account'] = $merchantId;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->appAuth('rzp_live', $this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testGetMerchantSupportedPlugins()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant->getId(),
            'business_website' => "https://www.google.com"
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->id);

        $this->ba->proxyAuth('rzp_test_' . $merchant->id, $merchantUser['id']);

        $this->startTest();
    }

    public function testIfSubMerchant()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->fixtures->create('merchant_access_map',[
                'merchant_id' => $merchant['id'],
            ]);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testGetMerchantPlugin()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000156']);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => '10000000000156',
            'plugin_details' => [
                [
                    'website' => "www.google.com",
                    'merchant_selected_plugin' => "shopify",
                    'suggested_plugin' => "whmcs"
                ]
            ]
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->id);

        $this->ba->proxyAuth('rzp_test_' . $merchant->id, $merchantUser['id']);

        $this->startTest();
    }
    public function testUpdateIfscCode()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testSubmit()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                                         'address_proof_url',
                                                         'business_pan_url',
                                                         'business_proof_url',
                                                         'promoter_address_url'
                                                     ]);

        $this->mockHubSpotClient('trackL2ContactProperties');

        $this->startTest();
    }

    public function testIsAdminLoggedInAsMerchant()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCanAccessActivationFormRoute()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id'     => '100000razorpay',
                'name'          => 'hide_activation_form',
                'entity_type'   => 'org',
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testActivationFormRouteBlockedByFeature()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id'     => '100000razorpay',
                'name'          => 'hide_activation_form',
                'entity_type'   => 'org',
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testSubmitAutoActivate()
    {
        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000000');

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $this->fixtures->edit('merchant', $merchantId, ['linked_account_kyc' => 0, 'parent_id' => '10000000000000']);

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' .$merchantId, $merchantUser['id']);

        $this->startTest();

        // assert legal entity data
        $legalEntity    = $this->getDbLastEntity('legal_entity');
        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        // asserting that penny testing should not happen for linked account
        $this->assertNull($merchantDetail->getPoaVerificationStatus());
        $this->assertNull($merchantDetail->getBankDetailsVerificationStatus());

        $this->assertEquals(1, $legalEntity->getBusinessTypeValue());
        $this->assertEquals($legalEntity->getMcc(), 8931);
        $this->assertEquals('financial_services', $legalEntity->getBusinessCategory());
        $this->assertEquals('accounting', $legalEntity->getBusinessSubcategory());
    }

    public function testSubmitWithInvalidFields()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:invalid_fields');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateIfscCodeWithFailure()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateEmail()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateEmails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateEmailWithFailure()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateDetailForLockedMerchant()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }

    public function testGetMerchantRejectionReasons()
    {
        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest();
    }

    public function testLockMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testAddClarificationReasons()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->mockAllSplitzTreatment();

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testAddClarificationReasonsNullFields()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->mockAllSplitzTreatment();

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testGetClarificationReasons()
    {
        $this->enableRazorXTreatmentForRazorX();

        $this->mockAllSplitzTreatment();

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $testData = $this->testData['testAddClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testAddClarificationReasonsNullFields'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->runRequestResponseFlow($testData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testAddNonGroupClarificationReasons()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    // PLSNL - Payments live Settlements Not Live nc count 1
    public function testEmailReminderForNcRevampPLSNL()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_payments_live_settlements_not_live_reminder', $mail->getTemplate());

            return true;
        });

    }

    // PLSNL - Payments live Settlements Not Live nc count 2
    public function testEmailReminderForNcRevampPLSNLCount2()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_2_payments_live_settlements_not_live_reminder', $mail->getTemplate());

            return true;
        });

    }

    // PLSL - Payments Live Settlements Live nc count 1
    public function testSendEmailReminderForNcRevampPLSL()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_payments_live_settlements_live_reminder', $mail->getTemplate());

            return true;
        });

    }

    // PLSL - Payments Live Settlements Live nc count 2
    public function testSendEmailReminderForNcRevampPLSLCount2()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_2_payments_live_settlements_live_reminder', $mail->getTemplate());

            return true;
        });

    }

    // PNL - Payments Not Live nc count 1
    public function testSendEmailReminderForNcRevampPNL()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => false,
            'activated'  => 0,
            'hold_funds' => true
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_payments_not_live_reminder', $mail->getTemplate());

            return true;
        });

    }

    // PNL - Payments Not Live nc count 2
    public function testSendEmailReminderForNcRevampPNLCount2()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => false,
            'activated'  => 0,
            'hold_funds' => true
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_2_payments_not_live_reminder', $mail->getTemplate());

            return true;
        });

    }

    // Onboarding Pause - Nc count 1
    public function testSendEmailReminderForNcRevampOnboardingPauseCount1()
    {
        Mail::fake();

        Config::set('applications.test_case.execution', false);

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => false,
            'activated'  => 0,
            'hold_funds' => true
        ]) ;

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => 'notenable', ]]]);

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_onboarding_pause_reminder', $mail->getTemplate());

            return true;
        });

    }
    // Onboarding Pause - Nc count 2
    public function testSendEmailReminderForNcRevampOnboardingPausecount2()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        Config::set('applications.test_case.execution', false);

        $merchant = $this->fixtures->create('merchant', [
            'live'       => false,
            'activated'  => 0,
            'hold_funds' => true
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(1)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been sent

        Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_2_onboarding_pause_reminder', $mail->getTemplate());

            return true;
        });

    }

    public function testSendEmailFailReminderForNcRevamp()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]) ;

        $merchantId = $merchant->getId();

        $this->fixtures->create('state',[
            'entity_id' => $merchantId,
            'name'    => 'needs_clarification',
            'entity_type' => 'merchant_detail',
            'created_at' => Carbon::now()->subDays(9)->getTimestamp()
        ]);

        $this->fixtures->create('clarification_detail',[
            'merchant_id' => $merchantId,
            'group_name' =>  'bank_details'
        ]);

        $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId(),
            'activation_status'     => 'needs_clarification'
        ]);

        (new NcRevampReminderCronJob(['cron_name' => 'nc_revamp_reminder']))->process();

        //verify email has been queued
        Mail::assertNotQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_payments_live_settlements_not_live_reminder', $mail->getTemplate());

            return true;
        });

    }

    // for new nc revamp

    public function testUnderReviewStateChange()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->mockAllSplitzTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id' => $merchant->getId()
        ]);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                          'address_proof_url',
                                          'business_pan_url',
                                          'business_proof_url',
                                          'promoter_address_url',
                                          'personal_pan',
                                          'cancelled_cheque',
                                      ]);

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        // add review notes for fields

        $testData = $this->testData['testAddClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        // put merchant to NC state

        $testData = $this->testData['changeActivationStatusToNeedsClarification'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        // put merchant to UR state

        $testData = $this->testData['changeActivationStatusToUnderReview'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        $result = (new Repository())->getByMerchantIdAndStatuses($merchantId, [\RZP\Models\ClarificationDetail\Constants::SUBMITTED, \RZP\Models\ClarificationDetail\Constants::NEEDS_CLARIFICATION]);

        $this->assertEquals(0, $result->count());

    }

    public function testValidateClarificationDetail()
    {
        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id' => $merchant->getId(),
        ]);

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->enableRazorXTreatmentForRazorX();

        $this->mockAllSplitzTreatment();

        // Adding clarification reasons

        $testData = $this->testData['testAddClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        // change activation status

        $testData = $this->testData['changeActivationStatusToNeedsClarification'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        // save group comments for merchant clarification reasons

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $testData = $this->testData['testSaveGroupCommentsMerchantClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        // asserting bad request exception for merchant clarification reasons missing field

        $testData = $this->testData['testSaveGroupMerchantClarificationReasonsMissingField'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->expectException(BadRequestValidationFailureException::class);

        $this->startTest($testData);
    }

    public function testGroupMerchantClarificationReasonsFlow()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->mockAllSplitzTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => true
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId()
        ]);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                          'address_proof_url',
                                          'business_pan_url',
                                          'business_proof_url',
                                          'promoter_address_url',
                                          'personal_pan',
                                          'cancelled_cheque',
                                      ]);

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchant->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);


        // add review notes for fields

        $testData = $this->testData['testAddClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);


        // put merchant to NC state

        $testData = $this->testData['changeActivationStatusToNeedsClarification'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        //verify email has been sent
        \Illuminate\Support\Facades\Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_payments_live_settlements_not_live', $mail->getTemplate());

            return true;
        });


        // save comments

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $testData = $this->testData['testSaveGroupCommentsMerchantClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertArraySubset([
                                     "nc_count" =>  1,
                                     "additional_details" =>  [],
                                     "clarification_reasons" =>  [
                                         "bank_branch_ifsc" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "SBIN0000202",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ],
                                         "cancelled_cheque" =>  [
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ],
                                         "bank_account_name" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "Ajay Kumar Brahma",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ],
                                         "bank_account_number" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "123456780",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ]
                                     ],
                                     "clarification_reasons_v2" =>  [
                                         "bank_account_number" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "123456780",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined",
                                                 "related_fields" =>  [
                                                     [
                                                         "field_name" =>  "cancelled_cheque"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_account_name"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_branch_ifsc"
                                                     ]
                                                 ]
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom",
                                                 "related_fields" =>  [
                                                     [
                                                         "field_name" =>  "cancelled_cheque"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_account_name"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_branch_ifsc"
                                                     ]
                                                 ]
                                             ]
                                         ]
                                     ]
                                 ],$merchantDetails->getKycClarificationReasons());

        $this->assertArraySubset(["issue_fields_reason"=> "Reason Details",
                                  "internal_notes"=> "Internal notes"],
                                 $merchantDetails->toArray());

        $testData = $this->testData['testSaveGroupMerchantClarificationReasonsDocValidation'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->expectException(BadRequestValidationFailureException::class);

        $this->startTest($testData);


        $document = $this->fixtures->create('merchant_document', [
            'id' => 'Km8g59o82Gw6IA',
            'document_type' => Type::CANCELLED_CHEQUE,
            'file_store_id' => '123123',
            'merchant_id'   => $merchantDetail['merchant_id'],
        ]);

        $testData = $this->testData['testSaveGroupMerchantClarificationReasonsMissingField'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->expectException(BadRequestValidationFailureException::class);

        $this->startTest($testData);

        $testData = $this->testData['testSaveGroupMerchantClarificationReasonsInvalidFeildData'];

        $this->expectException(BadRequestValidationFailureException::class);

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $testData = $this->testData['testSaveGroupMerchantClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNotEquals('icic0001232',$merchantDetails->getBankBranchIfsc());
        $this->assertNotEquals('test',$merchantDetails->getBankAccountName());
        $this->assertNotEquals('1234567892',$merchantDetails->getBankAccountNumber());

        // save notes after submitting group

        $testData = $this->testData['testSaveNotesForGroupClarifications'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertArraySubset([
                                     "nc_count" =>  1,
                                     "additional_details" =>  [],
                                     "clarification_reasons" =>  [
                                         "bank_branch_ifsc" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "SBIN0000202",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "updated new set of bank details. please verify now",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ],
                                         "cancelled_cheque" =>  [
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "updated new set of bank details. please verify now",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ],
                                         "bank_account_name" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "Ajay Kumar Brahma",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "updated new set of bank details. please verify now",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ],
                                         "bank_account_number" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "123456780",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom"
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "updated new set of bank details. please verify now",
                                                 "reason_type" =>  "custom"
                                             ]
                                         ]
                                     ],
                                     "clarification_reasons_v2" =>  [
                                         "bank_account_number" =>  [
                                             [
                                                 "from" =>  "admin",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "field_value" =>  "123456780",
                                                 "reason_code" =>  "bank_account_change_request_for_unregistered",
                                                 "reason_type" =>  "predefined",
                                                 "related_fields" =>  [
                                                     [
                                                         "field_name" =>  "cancelled_cheque"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_account_name"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_branch_ifsc"
                                                     ]
                                                 ]
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "Bank details are correct. Please check again",
                                                 "reason_type" =>  "custom",
                                                 "related_fields" =>  [
                                                     [
                                                         "field_name" =>  "cancelled_cheque"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_account_name"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_branch_ifsc"
                                                     ]
                                                 ]
                                             ],
                                             [
                                                 "from" =>  "merchant",
                                                 "nc_count" =>  1,
                                                 "is_current" =>  true,
                                                 "reason_code" =>  "updated new set of bank details. please verify now",
                                                 "reason_type" =>  "custom",
                                                 "related_fields" =>  [
                                                     [
                                                         "field_name" =>  "cancelled_cheque"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_account_name"
                                                     ],
                                                     [
                                                         "field_name" =>  "bank_branch_ifsc"
                                                     ]
                                                 ]
                                             ]
                                         ]
                                     ]
                                 ],$merchantDetails->getKycClarificationReasons());

        $this->assertArraySubset(["issue_fields_reason"=> "Reason Details",
                                  "internal_notes"=> "Internal notes"],
                                 $merchantDetails->toArray());

        //submit nc form

        $testData = $this->testData['testSubmitNCFormGroupFields'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals('icic0001232',$merchantDetails->getBankBranchIfsc());
        $this->assertEquals('test',$merchantDetails->getBankAccountName());
        $this->assertEquals('1234567892',$merchantDetails->getBankAccountNumber());

        //$this->assertEquals('under_review',$merchantDetails->getActivationStatus());

    }

    public function testNonGroupMerchantClarificationReasonsFlow()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => false,
            'activated'  => 0,
            'hold_funds' => true
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields',[
            'merchant_id'=>$merchant->getId()
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchant->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                          'address_proof_url',
                                          'business_pan_url',
                                          'business_proof_url',
                                          'promoter_address_url',
                                          'personal_pan',
                                          'cancelled_cheque',
                                      ]);

        $merchantId = $merchantDetail['merchant_id'];

        // put merchant to NC state validation error

        $testData = $this->testData['changeActivationStatusToNeedsClarificationWithoutReasons'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->expectException(BadRequestValidationFailureException::class);

        $this->startTest($testData);

        //add admin clarification reasons

        $testData = $this->testData['testAddNonGroupClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        // put merchant to NC state

        $testData = $this->testData['changeActivationStatusToNeedsClarification'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        //verify email has been sent
        \Illuminate\Support\Facades\Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_payments_not_live', $mail->getTemplate());

            return true;
        });

        //testAddNonGroupClarificationReasonsForMerchantInNC
        $testData = $this->testData['testAddNonGroupClarificationReasonsForMerchantInNC'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->expectException(BadRequestValidationFailureException::class);

        $this->startTest($testData);


        // save comments
       $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $testData = $this->testData['testAddCommentForNonGroupFieldsClarification'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertArraySubset([
                                     "nc_count" => 1,
                                     "additional_details" => [

                                     ],
                                     "clarification_reasons" => [
                                         "website" => [
                                             [
                                                 "from" => "admin",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "field_value" => "https://www.hello.com",
                                                 "reason_code" => "your website is not live",
                                                 "reason_type" => "custom"
                                             ],
                                             [
                                                 "from" => "merchant",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "reason_code" => "website is in progress of going live",
                                                 "reason_type" => "custom"
                                             ]
                                         ]
                                     ],
                                     "clarification_reasons_v2" => [
                                         "website" => [
                                             [
                                                 "from" => "admin",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "field_value" => "https://www.hello.com",
                                                 "reason_code" => "your website is not live",
                                                 "reason_type" => "custom"
                                             ],
                                             [
                                                 "from" => "merchant",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "reason_code" => "website is in progress of going live",
                                                 "reason_type" => "custom"
                                             ]
                                         ]
                                     ]
                                 ],$merchantDetails->getKycClarificationReasons());

        $this->assertArraySubset(["issue_fields_reason"=> "Reason Details",
                                  "internal_notes"=> "Internal notes"],
                                 $merchantDetails->toArray());

        // test submit validations
        $testData = $this->testData['testSubmitNCFormNonGroupFieldsWithoutGroupSubmission'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->expectException(BadRequestValidationFailureException::class);

        $this->startTest($testData);

        // submit group

        $testData = $this->testData['testSubmitForNonGroupFieldsClarification'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $testData = $this->testData['testAddNoteForNonGroupFieldsClarification'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertArraySubset([
                                     "nc_count" => 1,
                                     "additional_details" => [

                                     ],
                                     "clarification_reasons" => [
                                         "website" => [
                                             [
                                                 "from" => "admin",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "field_value" => "https://www.hello.com",
                                                 "reason_code" => "your website is not live",
                                                 "reason_type" => "custom"
                                             ],
                                             [
                                                 "from" => "merchant",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "reason_code" => "website is in progress of going live",
                                                 "reason_type" => "custom"
                                             ],
                                             [
                                                 "from" => "merchant",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "reason_code" => "website is going live this month",
                                                 "reason_type" => "custom"
                                             ]
                                         ]
                                     ],
                                     "clarification_reasons_v2" => [
                                         "website" => [
                                             [
                                                 "from" => "admin",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "field_value" => "https://www.hello.com",
                                                 "reason_code" => "your website is not live",
                                                 "reason_type" => "custom"
                                             ],
                                             [
                                                 "from" => "merchant",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "reason_code" => "website is in progress of going live",
                                                 "reason_type" => "custom"
                                             ],
                                             [
                                                 "from" => "merchant",
                                                 "nc_count" => 1,
                                                 "is_current" => true,
                                                 "reason_code" => "website is going live this month",
                                                 "reason_type" => "custom"
                                             ]
                                         ]
                                     ]
                                 ],$merchantDetails->getKycClarificationReasons());

        $this->assertArraySubset(["issue_fields_reason"=> "Reason Details",
                                  "internal_notes"=> "Internal notes"],
                                 $merchantDetails->toArray());

        //submit nc form

        $testData = $this->testData['testSubmitNCFormNonGroupFields'];

        $testData['request']['url'] = "/merchant/activation/clarifications";

        $this->startTest($testData);

        //$merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        //$this->assertEquals('under_review',$merchantDetails->getActivationStatus());
    }

    public function testNCRevampCommForPaymentsLiveSettlementLive()
    {
        Mail::fake();

        $this->enableRazorXTreatmentForRazorX();

        $this->mockAllSplitzTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'live'       => true,
            'activated'  => 1,
            'hold_funds' => false
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id' => $merchant->getId()
        ]);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                          'address_proof_url',
                                          'business_pan_url',
                                          'business_proof_url',
                                          'promoter_address_url',
                                          'personal_pan',
                                          'cancelled_cheque',
                                      ]);

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchant->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);
        // add review notes for fields

        $testData = $this->testData['testAddClarificationReasons'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/clarifications";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        // put merchant to NC state

        $testData = $this->testData['changeActivationStatusToNeedsClarification'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest($testData);

        //verify email has been sent
        \Illuminate\Support\Facades\Mail::assertQueued(MerchantOnboardingEmail::class, function($mail) {
            $this->assertEquals('emails.merchant.onboarding.nc_count_1_payments_live_settlements_live', $mail->getTemplate());

            return true;
        });
    }

    public function testGetMerchantActivationStatusChangeLog()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $this->fixtures->create('state', [
            'entity_id'   => $merchantId,
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review',
        ]);

        $this->fixtures->create('state', [
            'entity_id'   => $merchantId,
            'entity_type' => 'merchant_detail',
            'name'        => 'activated',
        ]);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        // fetch status change log for the merchant
        $testData['request']['url'] = "/merchant/activation/$merchantId/status_change_log";

        $testData['request']['method'] = 'GET';

        $testData['request']['content'] = [];

        $this->startTest();
    }

    public function testMerchantFormArchive()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/archive";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest();

        $testData['request']['content']['archive'] = 0;

        $testData['response']['content']['archived'] = 0;

        $this->startTest();
    }

    public function testMerchantActivationStatus()
    {
        Mail::fake();

        $merchantId = '1cXSLlUU8V9sXl';

        $website = 'http://abc.com';

        $this->fixtures->edit('merchant', $merchantId, ['website' => $website, 'whitelisted_domains' => ['abc.com']]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'business_website' => $website, 'issue_fields' => 'business_website']);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest();

        // under_review to needs_clarification
        $this->changeActivationStatusFromUnderReviewToNeedsClarification(
            $testData['request']['content'],
            $testData['response']['content']);

        $this->startTest();

        // needs_clarification to under_review
        $this->changeActivationStatusFromNeedsClarificationToUnderReview(
            $testData['request']['content'],
            $testData['response']['content']);

        $this->startTest();

        // under_review to rejected
        $this->changeActivationStatusFromUnderReviewToRejected(
            $testData['request']['content'],
            $testData['response']['content']);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertFalse($merchant->isActivated());

        $this->assertFalse($merchant->isLive());

        $this->assertTrue($merchant->getHoldFunds());

        Mail::assertQueued(Rejection::class, function ($mail)
        {
            $this->assertEquals('emails.merchant.rejection_notification', $mail->view);

            return true;
        });


    }

    protected function changeActivationStatusFromUnderReviewToNeedsClarification(& $requestContent, & $responseContent)
    {
        $requestContent['activation_status'] = 'needs_clarification';

        $requestContent['clarification_mode'] = 'email';

        $responseContent['activation_status'] = 'needs_clarification';

        $responseContent['clarification_mode'] = 'email';

        $responseContent['locked']             = true;
    }

    protected function changeActivationStatusFromNeedsClarificationToUnderReview(& $requestContent, & $responseContent)
    {
        $requestContent['activation_status'] = 'under_review';

        unset($requestContent['clarification_mode']);

        $responseContent['activation_status'] = 'under_review';

        unset($responseContent['clarification_mode']);
    }

    protected function changeActivationStatusFromUnderReviewToRejected(& $requestContent, & $responseContent)
    {
        // under_review to rejected
        $requestContent['activation_status'] = 'rejected';

        $requestContent['rejection_reasons'] = [
            [
                'reason_category' => 'risk_related_rejections',
                'reason_code'     => 'dedupe_blocked',
            ],
            [
                'reason_category' => 'risk_related_rejections',
                'reason_code'     => 'reject_on_risk_remarks',
            ],
        ];

        $responseContent['activation_status'] = 'rejected';
    }

    public function testMerchantDetailsPatch()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'whitelist');
    }

    public function testMerchantDetailsEditMobileNumber()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $this->fixtures->merchant->edit($merchant->getId(), ['country_code' => "MY"]);

        $merchant->setAttribute("country_code", "MY");
        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        $this->assertEquals($merchantDetails->getContactMobile(), '+60179164389');
    }

    public function testMerchantDetailsEditMobileNumberWithPrefix()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $this->fixtures->merchant->edit($merchant['id'], ['country_code' => "MY"]);

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        $this->assertEquals($merchantDetails->getContactMobile(), '+60179164389');
    }

    public function testMerchantDetailsEditMobileNumberIndia()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $merchant->setAttribute("country_code", "MY");
        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        $this->assertEquals($merchantDetails->getContactMobile(), '+919876543210');
    }

    public function testSmartDashboardMerchantDetailsPatch()
    {
        $this->markTestSkipped();

        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
        ]);

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $response = $this->startTest();

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::BUSINESS_OPERATION_ADDRESS,
            'fields'      => [
                [
                    'name'     => 'merchant_details|business_operation_address',
                    'value'    => 'Test address',
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|business_operation_address_l2',
                    'value'    => null,
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|business_operation_country',
                    'value'    => null,
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|business_operation_state',
                    'value'    => 'KA',
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|business_operation_city',
                    'value'    => 'Bengaluru',
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|business_operation_district',
                    'value'    => null,
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|business_operation_pin',
                    'value'    => '560030',
                    'editable' => true
                ],
            ],
        ], $response[MerchantConstants::MERCHANT_DETAILS][6]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::WEBSITE_LINK,
            'fields'      => [
                [
                    'name'     => 'merchant_details|merchant|website',
                    'value'    => 'https://www.test.com',
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|business_website',
                    'value'    => 'https://www.test.com',
                    'editable' => true
                ]
            ]
        ], $response[MerchantConstants::WEBSITE_DETAILS][0]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::PLAYSTORE_URL,
            'fields'      => [
                [
                    'name'     => 'merchant_details|playstore_url',
                    'value'    => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                    'editable' => true
                ],
                [
                    'name'     => 'merchant_details|merchant_business_detail|app_urls|playstore_url',
                    'value'    => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                    'editable' => true
                ]
            ]
        ], $response[MerchantConstants::WEBSITE_DETAILS][7]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::PG_USE_CASE,
            'fields'      => [
                [
                    'name'     => 'merchant_details|merchant_business_detail|pg_use_case',
                    'value'    => 'we have very good business use case, but we are in loss right now',
                    'editable' => true
                ]
            ]
        ], $response[MerchantConstants::MERCHANT_DETAILS][18]);
    }

    public function testMerchantDetailsFetchAccountServiceAccountDoesNotExist() {
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/accounts/'. '10000000000001';
        $this->ba->accountServiceAuth();
        $this->startTest($testData);
    }

    public function testMerchantDetailsFetchAccountService()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', ['business_category' => 'financial_services']);
        $merchant       = $merchantDetail->merchant;

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'percentage_ownership'=> 90, 'merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_email', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId(), 'entity_type' => 'stakeholder', 'document_type' => 'aadhar_front']);
        // test fetch account details by account service
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/accounts/'. $merchant->getId();

        $testData['response']['content']['merchant_website'] = null;
        $testData['response']['content']['merchant_business_detail'] = null;
        $this->ba->accountServiceAuth();
        $this->runRequestResponseFlow($testData);


        // Test merchant_website and merchant_business details are fetched
        $this->fixtures->create('merchant_website', [
                'merchant_id' => $merchant->getId(),
                'refund_process_period'=> "3-5 days",
                'admin_website_details'=> "{'website':'surkar.in'}"]
        );
        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            "business_parent_category"=> "ABC",
            "app_urls" => "{'app_url':'playstore.com/manthan/'}"
        ]);
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/accounts/'. $merchant->getId();
        $this->ba->accountServiceAuth();
        $this->runRequestResponseFlow($testData);


        // test fetch account details, where aadhar_back is classified as stakeholder document by default, if stakeholder is present
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId(), 'entity_type' => 'merchant', 'document_type' => 'aadhar_back']);
        array_push($testData['response']['content']['stakeholder_documents'], ['document_type'=>'aadhar_back']);
        $this->runRequestResponseFlow($testData);
    }

    public function testFetchAccountServiceStakeholderDocumentNoStakeholderEntity()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', ['business_category' => 'financial_services']);
        $merchant       = $merchantDetail->merchant;
        $this->fixtures->create('merchant_email', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId(), 'entity_type' => 'stakeholder', 'document_type' => 'aadhar_front']);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId(), 'entity_type' => 'merchant', 'document_type' => 'aadhar_back']);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/accounts/'. $merchant->getId();
        $this->ba->accountServiceAuth();
        $this->runRequestResponseFlow($testData);
    }

    public function testMerchantDetailsFetchAccountServiceNoStakeholder()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', ['business_category' => 'financial_services']);
        $merchant       = $merchantDetail->merchant;
        $this->fixtures->create('merchant_email', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId(), 'entity_type' => 'merchant', 'document_type' => 'aadhar_front']);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId(), 'entity_type' => 'merchant', 'document_type' => 'aadhar_back']);
        // test fetch account details by account service
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/accounts/'. $merchant->getId();
        $this->ba->accountServiceAuth();
        $this->runRequestResponseFlow($testData);
    }

    public function testUpdatedAccountsFetchAccountService()
    {
        // test fetch updated accounts without data
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/updated_accounts?from=1&duration=4&limit=2';
        $this->ba->accountServiceAuth();
        $this->runRequestResponseFlow($testData);

        //mids should appear
        $this->fixtures->create('stakeholder', ['updated_at'=> 1,'merchant_id' => "m2"]);
        $this->fixtures->create('merchant_email', ['updated_at'=> 2, 'merchant_id' => "m3"]);
        $this->fixtures->create('merchant_document', ['updated_at'=> 3, 'merchant_id' => "m4"]);
        $this->fixtures->create('merchant_website', ['updated_at'=> 5, 'merchant_id' => "m9"]);
        $this->fixtures->create('merchant_business_detail', ['updated_at'=> 5, 'merchant_id' => "m10"]);

        $merchantDetail = $this->fixtures->create('merchant_detail', ['updated_at'=> 1, 'business_category' => 'financial_services']);
        $merchant       = $merchantDetail->merchant;
        $merchantnew = $this->fixtures->create('merchant', ['updated_at'=> 1]);


        //duplicate mids should be removed
        $this->fixtures->create('stakeholder', ['updated_at'=> 2, 'merchant_id' => "m3"]);
        $this->fixtures->create('merchant_email', ['updated_at'=> 3, 'merchant_id' => "m4"]);
        $this->fixtures->create('merchant_document', ['updated_at'=> 4, 'merchant_id' => "m2"]);
        $this->fixtures->create('merchant_website', ['updated_at'=> 3, 'merchant_id' => "m9"]);
        $this->fixtures->create('merchant_business_detail', ['updated_at'=> 4, 'merchant_id' => "m10"]);

        //time out of range mids should not be considered
        $this->fixtures->create('stakeholder', ['updated_at'=> 0,'merchant_id' => "m78"]);
        $this->fixtures->create('merchant_email', ['updated_at'=> 6, 'merchant_id' => "m54"]);
        $this->fixtures->create('merchant_document', ['updated_at'=> 7, 'merchant_id' => "m7"]);
        $this->fixtures->create('merchant_website', ['updated_at'=> 8, 'merchant_id' => "m92"]);
        $this->fixtures->create('merchant_business_detail', ['updated_at'=> 8, 'merchant_id' => "m410"]);

        // test fetch updated accounts
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/updated_accounts?from=1&duration=4';
        $testData['response']['content']['count'] = 7;
        array_push($testData['response']['content']['account_ids'],
            $merchant->getId(), "m3", "m4" ,$merchantnew->getId(),"m2", "m9", "m10");

        $this->ba->accountServiceAuth();
        $this->runRequestResponseFlow($testData);

        // test fetch updated accounts with limit 2
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/updated_accounts?from=1&duration=4&limit=2';
        $testData['response']['content']['count'] = 2;
        array_push($testData['response']['content']['account_ids'], $merchant->getId(), "m3");

        // test fetch updated accounts with limit 1
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/updated_accounts?from=1&duration=4&limit=1';
        $testData['response']['content']['count'] = 1;
        array_push($testData['response']['content']['account_ids'], $merchant->getId());

        // test fetch updated accounts with limit 4
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/updated_accounts?from=1&duration=4&limit=4';
        $testData['response']['content']['count'] = 4;
        array_push($testData['response']['content']['account_ids'],
            $merchant->getId(), "m3", "m4" ,$merchantnew->getId());

        $this->ba->accountServiceAuth();
        $this->runRequestResponseFlow($testData);
    }

    public function testMerchantDetailsPatchShouldUpdateMethodsBasedOnCategory()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $methods = $merchant->methods;

        $methods->reload();

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        $merchant       = $merchantDetail->merchant->reload();

        $methods = $merchant->methods->reload();

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
        ];

        $this->assertArraySelectiveEquals($expectedMethods, $methods->toArray());

        $this->assertEquals($merchant->reload()->getCategory(), '4722');
    }

    public function testMerchantDetailsPatchShouldNotUpdateMethodsBasedOnCategoryIfResetMethodsIsFalse()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant       = $merchantDetail->merchant;

        $oldMethods = $merchant->methods->toArray();

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        $merchant       = $merchantDetail->merchant;

        $methods = $merchant->methods->reload();

        $this->assertArraySelectiveEquals($oldMethods, $methods->toArray());

        $this->assertEquals($merchant->reload()->getCategory(), '4722');
    }

    /**
     * Asserts the API response when the merchant context (X-Razorpay-Account header) is not set in the request
     */
    public function testMerchantDetailsPatchMerchantContextNotSet()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Asserts the API response when invalid business category - subcategory combination is provided
     */
    public function testMerchantDetailsPatchInvalidBusinessSubcategory()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    public function testMerchantDetailsPatchInvalidInternationalActivtionFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    public function testMerchantDetailsPatchBusinessNamePresent()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    public function testMerchantDetailsPatchValidStatusChange()
    {
        $attributes = [
            'submitted' => true,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attributes);
        $merchant       = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    public function testMerchantDetailsPatchInvalidStatusChange()
    {
        $attributes = [
            'bank_details_verification_status' => 'verified',
            'submitted'                        => true,
        ];
        $merchantDetail = $this->fixtures->create('merchant_detail', $attributes);
        $merchant       = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    /**
     * Asserts the API response when qthe business category and the subcategory are not updated.
     */
    public function testMerchantDetailsPatchNoBusinessCategorySubcategory()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    /**
     * Asserts the API response when qthe business category and the subcategory are not updated.
     */
    public function testMerchantDetailsPatchBusinessModel()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }
    public function testMerchantUpdateBusinessDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/business/detail";

        $this->ba->adminAuth();

        $this->startTest();
    }
    public function testMerchantUpdateMiqAndTestingHappy()
    {
        $this->fixtures->create('feature', [
            'name'          => 'additional_onboarding',
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/business/detail";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantUpdateMiqAndTestingUnHappyOrgFeature()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/business/detail";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantUpdateMiqAndTestingUnHappyInvalidDates()
    {
        $this->fixtures->create('feature', [
            'name'          => 'additional_onboarding',
            'entity_id'     => '100000razorpay',
            'entity_type'   => 'org',
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/business/detail";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMerchantUpdateWebsiteDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals($merchant->getWebsite(), 'https://www.example.com');
        $this->assertEquals($merchant->getHasKeyAccess() , true);
    }

    public function testMerchantUpdateWebsiteDetailsIpv6()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals($merchant->getWebsite(), 'https://cholasmartedisuat.chola.murugappa.com');
        $this->assertEquals($merchant->getHasKeyAccess() , true);
    }

    public function testAddMerchantActivationWebsiteDetailsWorkflowApprove()
    {
        Mail::fake();

        $this->setupWorkflow("update_website", PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL);

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails([], ['activation_status' => 'activated']);

        $this->mockStorkForBusinessWebsiteAdd();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId);

        $this->validateBusinessWebsiteWorkflowApprove($merchantId, $workflowActionId);

        $user = $this->getDbLastEntity('user');

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('https://www.example.com', $data['updated_business_website']);

            $this->assertEquals('emails.merchant.merchant_business_website_add', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });
    }

    protected function mockStorkForBusinessWebsiteAdd()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'updated_business_website' => 'https://www.example.com'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_business_website_add', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'As per your request, we have granted the API keys for the website https://www.example.com
You can follow these simple steps in the below URL to generate API keys.
https://razorpay.com/docs/api/#generate-api-key
We look forward to transacting with you!
-Team Razorpay',
            '1234567890'
        );
    }

    public function testMerchantDetailsFetchWithCustomText()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'cred'  => 1,
                ],
                'custom_text' => [
                    'cred' => 'discount of 20% with CRED coins'
                ]
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $request = [
            'url'       => '/merchants/details',
            'method'    => 'GET',
            'content'   => [],
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000000',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue(isset($response['methods']['custom_text']['cred']));
        $this->assertEquals('discount of 20% with CRED coins', $response['methods']['custom_text']['cred']);
    }

    public function testMerchantDetailsFetchWithInternalAppAuth()
    {
        $request = [
            'method'  => 'PUT',
            'url'     => '/merchants/10000000000000/methods',
            'content' => [
                'apps' => [
                    'cred'  => 1,
                ],
                'custom_text' => [
                    'cred' => 'discount of 20% with CRED coins'
                ]
            ] ,
        ];

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach('10000000000000');

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $request = [
            'url'       => '/internal/merchants/10000000000000/details',
            'method'    => 'GET',
            'content'   => [],
            'server' => [
            ]
        ];

        $this->ba->cmmaAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue(isset($response['methods']['custom_text']['cred']));
        $this->assertEquals('discount of 20% with CRED coins', $response['methods']['custom_text']['cred']);
    }

    public function testCommentMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testPreventEditingBuisnessNameInMIQ()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'business_name' =>'test 1',
            'business_dba'  => 'test'
        ]);

        $merchantDetail[Entity::BUSINESS_NAME] = "test business";

        $this->fixtures->org->addFeatures([FeatureConstants::ORG_PROGRAM_DS_CHECK],"100000razorpay");

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }


    public function testMerchantReviewer()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testCommentForLockedMerchant()
    {
        $params = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $params);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testCommentMerchantWithNoMerchantDetail()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $merchantId = $merchant['id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testUnlockMerchant()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testUnlockMerchant2()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testCreateMerchantDetailIfNotExist()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testZohoMerchantHeaders()
    {
        $this->fixtures->merchant->addFeatures(['zoho', 'charge_at_will']);
        $this->mandateHqTerminal = $this->fixtures->create('terminal:shared_mandate_hq_terminal');
        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');
        $this->mockCardVault();

        $payment = $this->getDefaultRecurringPaymentArray();

        $response = $this->doAuthAndCapturePayment($payment);

        // Set payment for second recurring payment
        unset($payment['card']);
        $payment['token'] = $response['token_id'];

        $data = $this->testData[__FUNCTION__];

        // Second recurring payment fails if attempted without the right headers
        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doS2sRecurringPayment($payment);
        });

        // Second recurring payment succeeds with the header
        $requestServer = [
            'HTTP_X_AGGREGATOR' => \Config::get('applications.zoho.header')
        ];

        $this->doS2sRecurringPayment($payment, $requestServer);
    }

    public function testMerchantDetailsFetch()
    {
        $this->enableRazorXTreatmentForRazorXRefund();

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000002',
                                                         'email' => 'razorpay@razorpay.com']);

        $this->fixtures->create('merchant:add_payment_banks', ['merchant_id' => '10000000000002']);

        $this->fixtures->merchant->enableInternational('10000000000002');

        $admin = $this->ba->getAdmin();

        $merchant->admins()->attach($admin);

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testMerchantDataFetch()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000002',
            'email' => 'razorpay@razorpay.com', 'country_code' => 'MY']);

        $data = $merchant->toArrayPublic();

        $this->assertEquals('MYR', $data['currency']);
    }

    public function testExternalGetMerchantCompositeDetails()
    {
        $merchant = $this->fixtures->create('merchant',[
            'id'        => '100000razorpay',
            'name'      => 'TestMerchant',
            'email'     => 'abc.def@gmail.com',
            'website'   => 'http://goyette.net/',
            'category'  => 1100,
        ]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'business_subcategory' => BusinessSubcategory::LENDING,
            'business_category'    => BusinessCategory::FINANCIAL_SERVICES,
        ]);

        $org = $this->fixtures->org->createAxisOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprAxisbToken', $org->getPublicId(), 'axisbank.com');

        $terminal = $this->fixtures->create(
            'terminal:shared_axis_terminal', ['id' =>'10000000000002',
            'used'        => true,
            'enabled'     => '1',
            'sync_status' => 'sync_success',
            'org_id'      => $org->getId(),
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $this->testData[__FUNCTION__]['request']['url'] = $url . $merchant->getId();

        $this->fixtures->create('feature', [
            'entity_id' => $org->getId(),
            'name'   => 'axis_org',
            'entity_type' => 'org',
        ]);

        $this->fixtures->create('feature', [
            'entity_id' => $merchant->getId(),
            'name'   => 'axis_access',
            'entity_type' => 'merchant',
        ]);

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->mockTerminalsServiceSendRequest(function () use ($terminal) {

            $data = $this->terminalRepository->findOrFail($terminal['id'])->toArrayWithPassword();

            $data['terminal_id'] = 'term_'.$data['id'];

            $data['entity'] = 'terminal';

            $body = json_encode(['data' => [$data]]);

            $response = new \WpOrg\Requests\Response;

            $response->body = $body;

            return $response;

        }, 1);

        $this->startTest();
    }

    public function testSmartDashboardMerchantDetailsFetch()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000155',
            'email' => 'razorpay@razorpay.com']);

        $merchantDetailData = [
            'merchant_id'        => '10000000000155',
            'business_type'      => "1",
            'transaction_volume' => "5",
            'department'         => "6",
            'contact_mobile'     => "8722627189",
            'contact_email'      => "razorpay@razorpay.com",
        ];

        $merchantAvgOrderData = [
            'merchant_id'        => '10000000000155',
            'min_aov'            => "94",
            'max_aov'            => "98"
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailData);

        $this->fixtures->create('merchant_avg_order_value', $merchantAvgOrderData);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url' => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ],
            'pg_use_case' => 'we have very good business use case, but we are in loss right now'
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'promoter_address_url',
            'file_store_id' => '123123',
            'merchant_id'   => $merchant->getId(),
        ]);

        $admin = $this->ba->getAdmin();

        $merchant->admins()->attach($admin);

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $response = $this->startTest();

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::BUSINESSTYPE,
            'fields'      => [
                [
                    'name'     => 'merchant_details|business_type',
                    'value'    => 'proprietorship',
                    'editable' => false
                ]
            ],
        ], $response[MerchantConstants::MERCHANT_DETAILS][0]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::CONTACT_NUMBER,
            'fields'      => [
                [
                    'name'     => 'merchant_details|contact_mobile',
                    'value'    => '+918722627189',
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::MERCHANT_DETAILS][10]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::CONTACT_EMAIL,
            'fields'      => [
                [
                    'name'     => 'merchant_details|contact_email',
                    'value'    => 'razorpay@razorpay.com',
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::MERCHANT_DETAILS][11]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::AVG_ORDER_VALUE,
            'fields'      => [
                [
                    'name'     => 'merchant_details|avg_order_min',
                    'value'    => 94,
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::MERCHANT_DETAILS][17]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::AVG_ORDER_VALUE,
            'fields'      => [
                [
                    'name'     => 'merchant_details|avg_order_max',
                    'value'    => 98,
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::MERCHANT_DETAILS][18]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::PG_USE_CASE,
            'fields'      => [
                [
                    'name'     => 'merchant_business_detail|pg_use_case',
                    'value'    => 'we have very good business use case, but we are in loss right now',
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::MERCHANT_DETAILS][19]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::PLAYSTORE_URL,
            'fields'      => [
                [
                    'name'     => 'merchant_details|playstore_url',
                    'value'    => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                    'editable' => false
                ],
                [
                    'name'     => 'merchant_business_detail|app_urls|playstore_url',
                    'value'    => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::WEBSITE_DETAILS][7]);

        $this->assertArraySelectiveEquals([
            'subcategory' => MerchantConstants::APPSTORE_URL,
            'fields'      => [
                [
                    'name'     => 'merchant_details|appstore_url',
                    'value'    => 'https://play.google.com/store/apps/details?id=com.dummy123123',
                    'editable' => false
                ],
                [
                    'name'     => 'merchant_business_detail|app_urls|appstore_url',
                    'value'    => 'https://play.google.com/store/apps/details?id=com.dummy123123',
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::WEBSITE_DETAILS][8]);

        $this->assertArraySelectiveEquals([
            'subcategory' => 'Promoter Address Url',
            'fields'      => [
                [
                    'name'     => 'documents|promoter_address_url',
                    'value'    => [
                        [
                            'file_store_id' => '123123',
                            'merchant_id'   => '10000000000155'
                        ]
                    ],
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::DOCUMENTS][31]);

        $this->assertArraySelectiveEquals([
            'subcategory' => 'Pricing',
            'fields'      => [
                    [
                        'name'     => 'pricing',
                        'value'    => 'pricing',
                        'editable' => false
                    ]
                ]
        ], $response[MerchantConstants::ADDITIONAL_DETAILS][0]);

        $this->assertArraySelectiveEquals([
            'subcategory' => 'Custom',
            'fields'      => [
                [
                    'name'     => 'custom',
                    'value'    => 'custom',
                    'editable' => false
                ]
            ]
        ], $response[MerchantConstants::ADDITIONAL_DETAILS][1]);
    }

    public function testGetInternalMerchantMerchantDetailsFetch()
    {
        $merchantId = '10000000000155';

        $merchant = $this->fixtures->create('merchant', [
            'id' => $merchantId,
            'email' => 'razorpay@razorpay.com',
            'website' => 'razorpay.com',
        ]);


        $this->fixtures->create('merchant_detail', [
            'merchant_id'        => $merchantId,
            'business_type'      => '1',
            'transaction_volume' => '5',
            'department'         => '6',
            'contact_mobile'     => '8722627189',
            'contact_email'      => 'razorpay@razorpay.com',
            'gstin'              => 'AAAA123456789A',
            'authorized_signatory_residential_address' => 'test',
            'authorized_signatory_dob' => '2022-03-12',
            'estd_year' => '2022',
        ]);

        $this->fixtures->create('merchant_avg_order_value', [
            'merchant_id'        => $merchantId,
            'min_aov'            => '94',
            'max_aov'            => '98',
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'app_urls' => [
                'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'appstore_url' => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'promoter_address_url',
            'file_store_id' => '123123',
            'merchant_id'   => $merchantId,
        ]);

        $testData = $this->testData['testGetInternalMerchantMerchantDetailsFetch'];

        $testData['request']['url'] = '/internal/merchants/' . $merchantId;

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->terminalsAuth();

        $this->startTest();
    }

    public function testGetPreSignupDetails()
    {
        $this->fixtures->create('merchant', ['id'    => '10000000000155',
                                             'email' => 'razorpay@razorpay.com']);
        $merchantDetailData = [
            'merchant_id'        => '10000000000155',
            'business_type'      => "1",
            'transaction_volume' => "5",
            'department'         => "6",
            'contact_mobile'     => "8722627189",
            'contact_email'      => "razorpay@razorpay.com",
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailData);

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000155');

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldNotReceive('trackOnboardingEvent');

        $this->ba->proxyAuth('rzp_test_10000000000155', $merchantUser['id']);

        $this->startTest();
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

    public function testPutPreSignupDetails($dataToReplace = [])
    {
        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->verifyOnboardingEvent('banking');

        $merchantDetail = $this->fixtures->create('merchant_detail', [Entity::CONTACT_MOBILE => '1234567890', 'merchant_id' => self::DEFAULT_MERCHANT_ID]);

        $this->mockBvsService();

        $this->fixtures->create('merchant_attribute',
                                [
                                    'merchant_id' => $merchantDetail['merchant_id'],
                                    'product'     => 'banking',
                                    'group'       => 'x_merchant_current_accounts',
                                    'type'        => 'ca_onboarding_flow',
                                    'value'       => 'ONE_CA'
                                ]);

        $testData = & $this->testData[__FUNCTION__];
        $testData['response']['content'][Entity::CONTACT_EMAIL] =  $merchantDetail[Entity::CONTACT_EMAIL];
        $testData['response']['content'][Entity::CONTACT_MOBILE] =  $merchantDetail[Entity::CONTACT_MOBILE];

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->mockHubSpotClient('trackPreSignupEvent');

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('trackOnboardingEvent')
                 ->times(2)
                 ->withArgs(function($eventData, $merchant, $ex, $actualData) {
                     if ($eventData['name'] == EventCode::X_CA_ONBOARDING_LEAD_UPSERT['name'])
                     {
                         $this->assertArraySelectiveEquals([
                             'lead_progress' => 'Pre signup lead',
                             'ca_onboarding_flow' => 'ONE_CA',
                         ], $actualData);
                     }
                     return true;
                 })
                 ->andReturnNull();

        $this->startTest($dataToReplace);

        $merchantConsents = (new MerchantConsentRepository())->getConsentDetailsForMerchantIdAndConsentFor($merchantDetail['merchant_id'], ['X_Terms of Use']);

        $termsDetails = (new MerchantConsentDetailsRepo())->getById($merchantConsents->getDetailsId());

        $expectedTerms = empty($dataToReplace) ? 'https://razorpay.com/x/terms/' : 'https://razorpay.com/x/terms/razorpayx/';

        $this->assertEquals($expectedTerms, $termsDetails->getURL());

        $this->assertEquals(\RZP\Models\Merchant\Consent\Constants::INITIATED, $merchantConsents->getStatus());
    }

    public function testPutPreSignupDetailsWithUtmParams()
    {
        $dataToReplace = [
            'request' => [
                'cookies' => [
                    'rzp_utm' => json_encode([
                        'website' => 'razorpay.com/x/current-accounts/' // Similar logic will be carried for other CA_PAGES
                    ])
                ]
            ]
        ];

        $this->testPutPreSignupDetails($dataToReplace);
    }

    public function testPutPreSignupDetailsForNeostone()
    {
        $this->mockBvsService();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->verifyOnboardingEvent('banking');

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('merchant_attribute',
                                [
                                    'merchant_id' => $merchantDetail['merchant_id'],
                                    'product'     => 'banking',
                                    'group'       => 'x_signup',
                                    'type'        => 'campaign_type',
                                    'value'       => 'ca_neostone'
                                ]);

        $this->fixtures->create('merchant_attribute',
                                [
                                    'merchant_id' => $merchantDetail['merchant_id'],
                                    'product'     => 'banking',
                                    'group'       => 'x_merchant_current_accounts',
                                    'type'        => 'ca_onboarding_flow',
                                    'value'       => 'ONE_CA'
                                ]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->mockHubSpotClient('trackPreSignupEvent');

        $this->mockSalesforceEventTracked('sendPreSignupDetails');

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('trackOnboardingEvent')
                 ->times(2)
                 ->withArgs(function($eventData, $merchant, $ex, $actualData) {
                     if ($eventData['name'] == EventCode::X_CA_ONBOARDING_LEAD_UPSERT['name'])
                     {
                         $this->assertArraySelectiveEquals([
                             'lead_progress' => 'Pre signup lead',
                             'ca_onboarding_flow' => 'ONE_CA',
                             ], $actualData);
                     }
                     return true;
                 })
                 ->andReturnNull();

        $this->startTest();
    }

    public function mockSalesforceEventTracked(string $methodName)
    {
        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
                                     ->setConstructorArgs([$this->app])
                                     ->setMethods([$methodName])
                                     ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        if (in_array($methodName, ['captureInterestOfPrimaryMerchantInBanking', 'sendPreSignupDetails']))
        {
            $salesforceClientMock->expects($this->exactly(1))
                                 ->method($methodName)
                                 ->will($this->returnCallback(function($input){

                                     $this->assertEquals('self_serve', $input['x_onboarding_category']);
                                 }));
        }
        else
        {
            $salesforceClientMock->expects($this->exactly(1))
                                 ->method($methodName);
        }
    }

    public function testPutPreSignupDetailsInXForUnregisteredBusiness()
    {
        $this->mockBvsService();

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testVaCreationTestModeInPreSignup()
    {
        $this->mockBvsService();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $pricingPlanId = $this->fixtures->create('pricing', [
            'product'        => 'banking',
            'id'             => '1zE31zbybacac1',
            'plan_id'        => '1hDYlICobzOCYt',
            'plan_name'      => 'testDefaultPlan',
            'feature'        => 'fund_account_validation',
            'payment_method' => 'bank_account',
            'percent_rate'   => 900,
            'org_id'         => '100000razorpay',
        ]);

        $merchant = $this->fixtures->merchant->edit('10000000000000', [
            'name'             => ' Kill Bill Pandey ',
            'billing_label'    => ' AB',
            'pricing_plan_id'  => $pricingPlanId['id'],
            'activated'        => false,
            'business_banking' => true,
            'international'    => 0,
            'category2'        => null
        ]);

        $this->verifyOnboardingEvent('banking');

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            Entity::BUSINESS_TYPE => '1',
            'merchant_id'         => '10000000000000',
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID], [], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                             'user_id'     => $user['id'],
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ], 'live');

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $this->assertEntitiesBankingNotNull($merchantDetail, $merchant->name);

        $bankAccountLiveMode = $this->getDbEntity('bank_account',
                                                  [
                                                      'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                      'type'        => 'virtual_account'
                                                  ], 'live');

        $this->assertNull($bankAccountLiveMode);

        $virtualAccountLiveMode = $this->getDbEntity('virtual_account',
                                                     [
                                                         'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID]
                                                     ], 'live');

        $this->assertNull($virtualAccountLiveMode);

        $balanceLiveMode = $this->getDbEntity('balance',
                                              [
                                                  'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                  'type'         => 'banking',
                                                  'account_type' => 'shared'
                                              ], 'live');

        $this->assertNull($balanceLiveMode);

        $bankingAccountLiveMode = $this->getDbEntity('banking_account',
                                                     [
                                                         'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                         'account_type' => 'nodal'
                                                     ], 'live');

        $this->assertNull($bankingAccountLiveMode);

    }

    public function testBeneficiaryNameInVirtualBankingAccounts()
    {
        $this->mockBvsService();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $pricingPlanId = $this->fixtures->create('pricing', [
            'product'        => 'banking',
            'id'             => '1zE31zbybacac1',
            'plan_id'        => '1hDYlICobzOCYt',
            'plan_name'      => 'testDefaultPlan',
            'feature'        => 'fund_account_validation',
            'payment_method' => 'bank_account',
            'percent_rate'   => 900,
            'org_id'         => '100000razorpay',
        ]);

        $merchant = $this->fixtures->merchant->edit('10000000000000', [
            'billing_label'    => ' A C ',
            'pricing_plan_id'  => $pricingPlanId['id'],
            'activated'        => false,
            'business_banking' => true,
            'international'    => 0,
            'category2'        => null
        ]);

        $this->verifyOnboardingEvent('banking');

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            Entity::BUSINESS_TYPE => '1',
            'merchant_id'         => '10000000000000',
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID], [], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                             'user_id'     => $user['id'],
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ], 'live');

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $this->assertEntitiesBankingNotNull($merchantDetail, $merchant->billing_label);
    }

    public function assertEntitiesBankingNotNull($merchantDetail, $labelOrName)
    {
        $bankAccount = $this->getDbEntity('bank_account',
                                          [
                                              'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                              'type'        => 'virtual_account'
                                          ], 'test');


        $this->assertNotNull($bankAccount);

        $entityId = $bankAccount['entity_id'];

        $bankAccountId = $bankAccount['id'];

        $this->assertEquals(trim($labelOrName), $bankAccount['beneficiary_name']);

        $virtualAccount = $this->getDbEntity('virtual_account',
                                             [
                                                 'merchant_id'     => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                 'id'              => $entityId,
                                                 'bank_account_id' => $bankAccountId
                                             ], 'test');

        $this->assertNotNull($virtualAccount);

        $this->assertEquals(trim($labelOrName), $virtualAccount['name']);

        $balanceId = $virtualAccount['balance_id'];

        $balance = $this->getDbEntity('balance',
                                      [
                                          'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                          'type'         => 'banking',
                                          'account_type' => 'shared',
                                          'id'           => $balanceId
                                      ], 'test');

        $this->assertNotNull($balance);

        $accountNumber = $balance['account_number'];

        $bankingAccount = $this->getDbEntity('banking_account',
                                             [
                                                 'merchant_id'    => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                 'account_type'   => 'nodal',
                                                 'account_number' => $accountNumber,
                                                 'balance_id'     => $balanceId
                                             ], 'test');

        $this->assertNotNull($bankingAccount);

        $this->assertEquals(trim($labelOrName), $bankingAccount['beneficiary_name']);
    }

    public function testVaNotCreatedForBusinessBankingDisabledInTestModePreSignup()
    {
        $this->mockBvsService();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $pricingPlanId = $this->fixtures->create('pricing', [
            'product'        => 'banking',
            'id'             => '1zE31zbybacac1',
            'plan_id'        => '1hDYlICobzOCYt',
            'plan_name'      => 'testDefaultPlan',
            'feature'        => 'fund_account_validation',
            'payment_method' => 'bank_account',
            'percent_rate'   => 900,
            'org_id'         => '100000razorpay',
        ]);

        $this->fixtures->merchant->edit('10000000000000', [
            'pricing_plan_id'  => $pricingPlanId['id'],
            'activated'        => false,
            'business_banking' => false,
            'international'    => 0,
            'category2'        => null
        ]);

        $this->verifyOnboardingEvent('banking');

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            Entity::BUSINESS_TYPE => '1',
            'merchant_id'         => '10000000000000',
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID], [], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                             'user_id'     => $user['id'],
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ], 'live');

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $bankAccount = DB::table('bank_accounts')
                         ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                         ->where('type', '=', 'virtual_account')
                         ->get();

        $this->assertTrue(count($bankAccount) === 0);

        $virtualAccount = DB::table('virtual_accounts')
                            ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                            ->get();

        $this->assertTrue(count($virtualAccount) === 0);

        $balance = DB::table('balance')
                     ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                     ->where('type', '=', 'banking')
                     ->where('account_type', '=', 'shared')
                     ->get();

        $this->assertTrue(count($balance) === 0);

        $bankingAccount = DB::table('banking_accounts')
                            ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                            ->where('account_type', '=', 'nodal')
                            ->get();

        $this->assertTrue(count($bankingAccount) === 0);

        $bankAccountLiveMode = $this->getDbEntity('bank_account',
                                                  [
                                                      'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                      'type' => 'virtual_account'
                                                  ], 'live');

        $this->assertNull($bankAccountLiveMode);

        $virtualAccountLiveMode = $this->getDbEntity('virtual_account',
                                                     [
                                                         'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID]
                                                     ], 'live');

        $this->assertNull($virtualAccountLiveMode);

        $balanceLiveMode = $this->getDbEntity('balance',
                                              [
                                                  'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                  'type'         => 'banking',
                                                  'account_type' => 'shared'
                                              ], 'live');

        $this->assertNull($balanceLiveMode);

        $bankingAccountLiveMode = $this->getDbEntity('banking_account',
                                                     [
                                                         'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                         'account_type' => 'nodal'
                                                     ], 'live');

        $this->assertNull($bankingAccountLiveMode);

    }

    public function testVaNotCreatedInTestModeWhenMockedPreSignup()
    {
        $this->mockBvsService();

        $this->mockRazorxTreatment();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $pricingPlanId = $this->fixtures->create('pricing', [
            'product'        => 'banking',
            'id'             => '1zE31zbybacac1',
            'plan_id'        => '1hDYlICobzOCYt',
            'plan_name'      => 'testDefaultPlan',
            'feature'        => 'fund_account_validation',
            'payment_method' => 'bank_account',
            'percent_rate'   => 900,
            'org_id'         => '100000razorpay',
        ]);

        $this->fixtures->merchant->edit('10000000000000', [
            'pricing_plan_id'  => $pricingPlanId['id'],
            'activated'        => false,
            'business_banking' => true,
            'international'    => 0,
            'category2'        => null
        ]);

        $this->verifyOnboardingEvent('banking');

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            Entity::BUSINESS_TYPE => '1',
            'merchant_id'         => '10000000000000',
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID], [], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                             'user_id'     => $user['id'],
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ], 'live');

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->ba->proxyAuth('rzp_live_'.$merchantDetail['merchant_id'], $user->getId());

        $this->startTest();

        $bankAccount = DB::table('bank_accounts')
                         ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                         ->where('type', '=', 'virtual_account')
                         ->get();

        $this->assertTrue(count($bankAccount) === 0);

        $virtualAccount = DB::table('virtual_accounts')
                            ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                            ->get();

        $this->assertTrue(count($virtualAccount) === 0);

        $balance = DB::table('balance')
                     ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                     ->where('type', '=', 'banking')
                     ->where('account_type', '=', 'shared')
                     ->get();

        $this->assertTrue(count($balance) === 0);

        $bankingAccount = DB::table('banking_accounts')
                            ->where('merchant_id', '=', $merchantDetail[MerchantDetails::MERCHANT_ID])
                            ->where('account_type', '=', 'nodal')
                            ->get();

        $this->assertTrue(count($bankingAccount) === 0);

        $bankAccountLiveMode = $this->getDbEntity('bank_account',
                                                  [
                                                      'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                      'type' => 'virtual_account'
                                                  ], 'live');

        $this->assertNull($bankAccountLiveMode);

        $virtualAccountLiveMode = $this->getDbEntity('virtual_account',
                                                     [
                                                         'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID]
                                                     ], 'live');

        $this->assertNull($virtualAccountLiveMode);

        $balanceLiveMode = $this->getDbEntity('balance',
                                              [
                                                  'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                  'type'         => 'banking',
                                                  'account_type' => 'shared'
                                              ], 'live');

        $this->assertNull($balanceLiveMode);

        $bankingAccountLiveMode = $this->getDbEntity('banking_account',
                                                     [
                                                         'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                         'account_type' => 'nodal'
                                                     ], 'live');

        $this->assertNull($bankingAccountLiveMode);

    }

    public function testPutPreSignupDetailsForUnregisteredBusiness()
    {
        $this->verifyOnboardingEvent('primary');

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            Entity::BUSINESS_TYPE => '11'
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID], [], 'owner', 'live');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                                                             'user_id'     => $user['id'],
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ], 'live');


        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $merchantDetail = $this->getDbEntity('merchant_detail',
                                             [
                                                 'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID]
                                             ]);

        $this->assertEquals($merchantDetail[Entity::CONTACT_NAME], $merchantDetail[Entity::BUSINESS_NAME]);
    }

    public function testPutPreSignupDetailsWithCouponCode()
    {
        $this->ba->adminAuth();

        $promotion = $this->fixtures->on('live')->create('promotion:onetime');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => 'RANDOM',
        ];

        $coupon = $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id'], [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $merchantPromotion = $this->getDbEntity('merchant_promotion',
                                                [
                                                    'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID]
                                                ], 'live')
                                  ->toArray();

        $this->assertSame(1, $merchantPromotion['remaining_iterations']);
    }

    public function testPutPreSignupDetailsWithInvalidCouponCode()
    {
        $this->ba->adminAuth();

        $promotion = $this->fixtures->on('live')->create('promotion:onetime');

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code' => 'RANDOM-123',
        ];

        $coupon = $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testPutPreSignupDetailsWithSystemCouponCode()
    {
        $this->ba->adminAuth();

        $couponAttributes = [
            'entity_id'   => 'HenDpL3bx1eJJX',
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code' => 'OFFERMTU2',
        ];

        $coupon = $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testPutPreSignupDetailsWithPartnerCouponCodeForBanking()
    {
        $this->mockBvsService();

        $this->ba->adminAuth();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $promotionAttributes = [
            'partner_id' => self::DEFAULT_MERCHANT_ID,
            'product' => 'banking'
        ];

        $promotion = $this->fixtures->on('live')->create('promotion:onetime', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => 'RANDOM',
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail[MerchantDetails::MERCHANT_ID], [], 'owner', 'live');

        $razorxMock = $this->getMockBuilder(Core::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $razorxMock->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $this->mockSalesforceEventTracked('sendPartnerLeadInfo');

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail[MerchantDetails::MERCHANT_ID], $merchantUser['id']);

        $this->startTest();

        $merchantPromotion = $this->getDbEntity('merchant_promotion',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID]
            ], 'live')
            ->toArray();

        $this->assertSame(1, $merchantPromotion['remaining_iterations']);
    }

    public function testFetchMerchantConsents()
    {
        $this->testPutPreSignupDetails();

        $dataToReplace = [
            'request' => [
                'url'     => '/merchant/consents/'. self::DEFAULT_MERCHANT_ID,
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->startTest($dataToReplace);

        $this->assertCount(2, $response);
    }

    public function testFetchMerchantConsentsForPg()
    {
        $this->fixtures->create('merchant_consents',
            [
                'merchant_id' => self::DEFAULT_MERCHANT_ID,
                'consent_for' => 'Partnership_Terms & Conditions',
                'status'      => 'initiated'
            ]);


        $this->mockCreateLegalDocument();;

        $this->ba->adminAuth();

        $testData = $this->testData['testFetchMerchantConsents'];

        $testData['request']['url'] = '/merchant/consents/'. self::DEFAULT_MERCHANT_ID;

        $response = $this->runRequestResponseFlow($testData);

        $this->assertCount(1, $response);
    }

    public function testBulkAssignReviewer()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testMerchantsMtuUpdateSuccess()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->ba->mtuLambdaAuth();

        $this->startTest();

        $testMerchant = $this->getDbEntityById('merchant', '10000000000000', 'test');
        $this->assertSame('1', $testMerchant->merchantDetail->getLiveTransactionDone());


        $liveMerchant = $this->getDbEntityById('merchant', '10000000000000', 'live');
        $this->assertSame('1', $liveMerchant->merchantDetail->getLiveTransactionDone());
    }

    public function testMerchantsMtuUpdateIdFailure()
    {
        $this->ba->mtuLambdaAuth();

        $this->startTest();
    }

    public function testMerchantsMtuUpdateLiveTransactionFailure()
    {
        $this->ba->mtuLambdaAuth();

        $this->startTest();
    }

    public function testSendRequestDocumentWhatsappNotificationFailure()
    {
        $this->ba->adminAuth();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkOptInStatusForWhatsapp($storkMock, '+911234567890');

        $ticketDetails["fd_instance"] = 'rzpsol';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => '10000000000000',
            'contact_mobile'    => '1234567890'
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0013',
            'ticket_id'      => '123',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->startTest();
    }

    public function testSendRequestDocumentWhatsappNotification()
    {
        $this->ba->adminAuth();

        $this->mockStorkForRequestDocumentWhatsappNotification();

        $ticketDetails["fd_instance"] = 'rzpind';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => '10000000000000',
            'contact_mobile'    => '1234567890'
        ]);

        $this->fixtures->create('merchant_freshdesk_tickets', [
            'id'             => 'razorpayid0013',
            'ticket_id'      => '123',
            'merchant_id'    => '10000000000000',
            'type'           => 'support_dashboard',
            'ticket_details' => $ticketDetails,
        ]);

        $this->startTest();
    }

    protected function mockStorkForRequestDocumentWhatsappNotification()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkOptInStatusForWhatsapp($storkMock, '+911234567890');

        $this->expectStorkWhatsappRequest($storkMock, 'Hi {1},
Thanks for choosing Razorpay. There are a few requirements that need to be completed before we can activate your account.
- {2}
- {3}
- {4}

Please share the links/proofs by replying to this ticket.
Regards,
Team Razorpay', '+911234567890');
    }

    public function testGetRequestDocumentList()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkEditMerchantAttributes()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        // Put CSV file as UploadedFile instance in request
        $path = __DIR__ . '/helpers/bulk-edit-merchant-attributes.csv';
        $file = new UploadedFile($path, 'file.csv', 'text/csv', null, true);
        $this->testData[__FUNCTION__]['request']['files']['file'] = $file;

        // Fire api request and assert response and entity state in both modes
        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('live', $this->authToken, $this->org->getPublicId());

        $this->startTest();

        $testMerchant = $this->getDbEntityById('merchant', '10000000000000', 'test');
        $this->assertSame('KE', $testMerchant->merchantDetail->getBusinessRegisteredState());
        $this->assertSame('kerala@test.com', $testMerchant->merchantDetail->getContactEmail());

        $liveMerchant = $this->getDbEntityById('merchant', '10000000000000', 'live');
        $this->assertSame('KE', $liveMerchant->merchantDetail->getBusinessRegisteredState());
        $this->assertSame('kerala@test.com', $liveMerchant->merchantDetail->getContactEmail());
    }

    /**
     * The merchant tries to update the fields critical to instant activations after he has been activated.
     */
    public function testUpdateCriticalFieldsPostActivation()
    {
        $attributes = [
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::MUTUAL_FUND,
            MerchantDetails::BUSINESS_CATEGORY    => BusinessCategory::FINANCIAL_SERVICES,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $attributes);

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->fixtures->merchant->activate($merchantId);

        $this->startTest();
    }
    /**
     * The merchant tries to update the fields critical to instant activations after he has been activated after onboarding with easy dashboard
     */
    public function testUpdateCriticalFieldsPostActivationEasyOnboarding()
    {
        $attributes = [
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::MUTUAL_FUND,
            MerchantDetails::BUSINESS_CATEGORY    => BusinessCategory::FINANCIAL_SERVICES,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $attributes);

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->fixtures->merchant->activate($merchantId);

        $this->startTest();
    }

    /**
     * The merchant tries to update the fields not critical to instant activations after he has been activated.
     * An activated merchant will submit the other details using this API to complete the KYC.
     */
    public function testUpdateNonCriticalFieldsPostActivation()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail['merchant_id'];

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->fixtures->merchant->activate($merchantId);

        $this->startTest();
    }

    /**
     * checks that category and category2 details should be set on business subcategory change
     */
    public function testCategoryDetailsSetOnSubCategoryChange()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::LENDING,
            MerchantDetails::BUSINESS_CATEGORY    => BusinessCategory::FINANCIAL_SERVICES,
        ]);

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
     * checks that category and category2 details should be set on business category changed to others
     * this is a special case as business subcategory field will be null
     */
    public function testCategoryDetailsSetForOthersCategory()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::LENDING,
            MerchantDetails::BUSINESS_CATEGORY    => BusinessCategory::FINANCIAL_SERVICES,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('5399', $liveMerchant->getCategory());
        $this->assertSame('ecommerce', $liveMerchant->getCategory2());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame('5399', $testMerchant->getCategory());
        $this->assertSame('ecommerce', $testMerchant->getCategory2());
    }

    /**
     * blacklist activation flow should not be allowed to submit full activation form
     */
    public function testUnsupportedActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST
        ]);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                                         'address_proof_url',
                                                         'business_pan_url',
                                                         'business_proof_url',
                                                         'promoter_address_url'
                                                     ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testBlacklistActivationFlowEasyOnboarding()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetail->getMerchantId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();

        $merchant = $this->getDbLastEntity('merchant');

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertTrue($merchantDetail['locked']);

        $this->assertFalse($merchant['live']);

        $this->assertFalse($merchant['activated']);

        $this->assertTrue($merchant['hold_funds']);
    }

    public function testBlacklistActivationFlowCanSubmit()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST,
            MerchantDetails::BUSINESS_CATEGORY => BusinessCategory::ECOMMERCE,
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::WEAPONS_AND_AMMUNITIONS,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    /**
     * whitelist and greylist activation flow should be allowed to fill full activation form
     */
    public function testSupportedActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            MerchantDetails::ACTIVATION_FLOW => ActivationFlow::WHITELIST
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    /**
     * Asserts the category and category2 are populated on business category or business subcategory change
     */
    public function testMerchantDetailsPatchCategoryAutoPopulation()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('6211', $liveMerchant->getCategory());
        $this->assertSame('mutual_funds', $liveMerchant->getCategory2());
    }


    /**
     * Asserts that website and name detail should be in sync between merchant and merchant detail entity
     */
    public function testWebsiteDetailsShouldBeInSync()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals($merchant->getWebsite(), 'https://example.com');
        $this->assertEquals($merchant->getName(), 'facebook');

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);
        $this->assertEquals($merchantDetails->getWebsite(), 'https://example.com');
        $this->assertEquals($merchantDetails->getBusinessName(), 'facebook');
    }

    public function testStoreCaseInsensitiveDomain()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $website = 'http://abc.com';

        $this->fixtures->edit('merchant', $merchantId, ['website' => $website, 'whitelisted_domains' => ['abc.com']]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'business_website' => $website]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertNotContains('abc.com',$merchant->getWhitelistedDomains());
        $this->assertContains('example.com',$merchant->getWhitelistedDomains());

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['business_website'] = '';
        $testData['response']['content']['business_website'] = '';

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals($merchant->getWhitelistedDomains(),[]);

    }

    public function testFileUploadSyncInDetailAndDocumentTable()
    {
        $merchantId = "1cXSLlUU8V9sXl";

        $documentType = MerchantDetails::PROMOTER_PAN_URL;

        $fileStoreId = 'DG7xtA4fkoXNaa';

        $merchantDetail = $this->fixtures->create(Constants\Entity::MERCHANT_DETAIL, [
            MerchantDetails::MERCHANT_ID => $merchantId,
            $documentType                => $fileStoreId,
        ]);

        $this->fixtures->create(Constants\Entity::MERCHANT_DOCUMENT, [
            MerchantDocuments::MERCHANT_ID   => $merchantId,
            MerchantDocuments::DOCUMENT_TYPE => $documentType,
            MerchantDocuments::FILE_STORE_ID => $fileStoreId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->updateUploadDocumentData(__FUNCTION__, $documentType);

        $this->startTest();

        $merchantDetail = $this->getDbEntityById(Constants\Entity::MERCHANT_DETAIL, $merchantId);

        $this->assertNotEquals($merchantDetail->getAttribute($documentType), $fileStoreId);

        $document = $this->getDbEntity(Constants\Entity::MERCHANT_DOCUMENT, [MerchantDocuments::FILE_STORE_ID => $fileStoreId]);

        $this->assertNULL($document);
    }

    public function testFileUploadSyncDetailAndDocumentUploadedToUFH()
    {
        $this->testFileUploadSyncInDetailAndDocumentTable();

        $merchantDocumentEntry = $this->getLastEntity('merchant_document', true, 'test');

        $this->assertEquals($merchantDocumentEntry['source'], Source::UFH);
    }

    public function updateUploadDocumentData(string $callee, string $documentType)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files'][$documentType] = new UploadedFile(
            __DIR__ . '/../Storage/a.png',
            'a.png',
            'image/png',
            null,
            true);
    }

    public function testUpdateKYCClarificationReason()
    {
        // used for mocking meta data request's response to BVS, artefact common.
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->mockRazorX(__FUNCTION__, 'BVS_MANUAL_VERIFICATION_DATA', 'on', $merchantId);

        $this->startTest();

        // Tests that a new row is added in bvs_validation table with common artefact_type for this merchant.
        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $expected = [
            "merchant_id" =>  $merchantId,
            "artefact_type" => "common"
        ];

        $actual  = [
            "merchant_id" => $bvsValidationEntity['owner_id'],
            "artefact_type" => $bvsValidationEntity['artefact_type']
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testUpdateKYCClarificationReasonWithFailure()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testUpdateKycAdditionalDetails()
    {
        $testData = &$this->testData['testUpdateKycAdditionalDetailsData'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $testData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateKycAdditionalDetailsWithFailure()
    {
        $testData = &$this->testData['testUpdateKycAdditionalDetailsData'];

        unset($testData['kyc_clarification_reasons']['additional_details']);

        $merchantDetail = $this->fixtures->create('merchant_detail', $testData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateKycAdditionalDetailsWithInvalidField()
    {
        $testData = &$this->testData['testUpdateKycAdditionalDetailsData'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $testData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testAdditionalWebsite()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/websites";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertContains('example.com', $merchant->getWhitelistedDomains());
    }

    public function testAdditionalWebsiteMaxLimitFailure()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $websites = $this->getCollectionOfWebsites(15);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'additional_websites' => $websites]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/websites";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
    }

    public function testWebsiteNotLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id" => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Http::fake(['http://razorpays.com/' => Http::response([], 400, []),]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testPopularWebsite()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id" => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Http::fake(['http://google.com/' => Http::response([], 200, []),]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantDetails = $this->getLastEntity('merchant_detail',true);

        $this->assertNull($merchantDetails['business_website']);
    }

    public function testWebsiteNotLiveSplitzKqu()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id" => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Http::fake(['http://razorpays.com/' => Http::response([], 400, []),]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testPopularWebsiteSplitzKqu()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id" => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Http::fake(['http://google.com/' => Http::response([], 200, []),]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantDetails = $this->getLastEntity('merchant_detail',true);

        $this->assertNull($merchantDetails['business_website']);
    }

    public function testWebsiteNotLiveSplitzOff()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id" => $merchantId
        ];

        $this->mockSplitzTreatment($input);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testWebsiteNotLiveSplitzPilot()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id" => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'pilot',
                ]
            ]
        ];

        Http::fake(['http://razorpays.com/' => Http::response([], 400, []),]);

        $this->mockSplitzTreatment($input, $output);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testCINSignatorySuccessExperimentLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_type' => 4,
            'cin_verification_status'=>'pending'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'cin', 'merchant_id' => $merchantId]);

        $bvsValidation = $this->getDbLastEntity('bvs_validation')->toArray();

        $expectedMetadata = ["signatory_validation_status"=> "verified", 'bvs_validation_id'=>$bvsValidation['validation_id']];

        $this->assertEquals($expectedMetadata, $merchantVerificationDetail['metadata']);

    }


    public function testCINSignatoryFailureExperimentLiveAsync()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_type' => 4,
            'cin_verification_status'=>'pending'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $bvsValidation = $this->getDbLastEntity('bvs_validation', 'live');

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation['validation_id'],
                'status'            => 'failed',
                'error_description' => 'cin: must be in a valid format.',
                'error_code'        => 'VALIDATION_ERROR'
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation['validation_id']);

        $verificationDetail = $this->getDbEntityById('merchant_detail', '1cXSLlUU8V9sXl');

        $this->assertEquals('failed', $bvsValidation->getValidationStatus());

        $this->assertEquals('incorrect_details', $verificationDetail->getCinVerificationStatus());


        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'cin', 'merchant_id' => $merchantId]);

        $this->assertEquals(["signatory_validation_status"=> "not_initiated", 'bvs_validation_id'=>$bvsValidation['validation_id']],$merchantVerificationDetail['metadata']);

    }

    public function testCINSignatorySuccessExperimentLiveAsync()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);


        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
            'business_type' => 4,
            'cin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $bvsValidation = $this->getDbLastEntity('bvs_validation', 'live');

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'     => $bvsValidation['validation_id'],
                'status'            => 'success',
                'error_description' => '',
                'error_code'        => '',
                'rule_execution_list' => [
                    0 => [
                        'rule' => [
                            'rule_type' => 'string_comparison_rule',
                            'rule_def' => [
                                'or' => [
                                    0 => [
                                        'fuzzy_wuzzy' => [
                                            0 => [
                                                'var' => 'artefact.details.legal_name.value',
                                            ],
                                            1 => [
                                                'var' => 'enrichments.online_provider.details.legal_name.value',
                                            ],
                                            2 => 70,
                                        ],
                                    ],
                                    1 => [
                                        'fuzzy_wuzzy' => [
                                            0 => [
                                                'var' => 'artefact.details.trade_name.value',
                                            ],
                                            1 => [
                                                'var' => 'enrichments.online_provider.details.trade_name.value',
                                            ],
                                            2 => 70,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'rule_execution_result' => [
                            'result' => true,
                            'operator' => 'or',
                            'operands' => [
                                'operand_1' => [
                                    'result'   => false,
                                    'operator' => 'fuzzy_wuzzy',
                                    'operands' => [
                                        'operand_1' => 'Rzp Test QA Merchant',
                                        'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                        'operand_3' => 70,
                                    ],
                                    'remarks' => [
                                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                        'match_percentage'      => 45,
                                        'required_percentage'   => 70,
                                    ],
                                ],
                                'operand_2' => [
                                    'result'   => false,
                                    'operator' => 'fuzzy_wuzzy',
                                    'operands' => [
                                        'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                                        'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                        'operand_3' => 70,
                                    ],
                                    'remarks' => [
                                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                        'match_percentage'      => 68,
                                        'required_percentage'   => 70,
                                    ],
                                ],
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 68,
                                'required_percentage'   => 70,
                            ],
                        ],
                        'error' => '',
                    ],
                    1 => [
                        'rule' => [
                            'rule_type' => 'array_comparison_rule',
                            'rule_def' => [
                                'some' => [
                                    0 => [
                                        'var' => 'enrichments.online_provider.details.signatory_names',
                                    ],
                                    1 => [
                                        'fuzzy_wuzzy' => [
                                            0 => [
                                                'var' => 'each_array_element',
                                            ],
                                            1 => [
                                                'var' => 'artefact.details.legal_name.value',
                                            ],
                                            2 => 70,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'rule_execution_result' => [
                            'result'   => true,
                            'operator' => 'some',
                            'operands' => [
                                'operand_1' => [
                                    'result'   => false,
                                    'operator' => 'fuzzy_wuzzy',
                                    'operands' => [
                                        'operand_1' => 'HARSHILMATHUR ',
                                        'operand_2' => 'Rzp Test QA Merchant',
                                        'operand_3' => 70,
                                    ],
                                    'remarks' => [
                                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                        'match_percentage'      => 30,
                                        'required_percentage'   => 70,
                                    ],
                                ],
                                'operand_2' => [
                                    'result'   => false,
                                    'operator' => 'fuzzy_wuzzy',
                                    'operands' => [
                                        'operand_1' => 'Shashank kumar ',
                                        'operand_2' => 'Rzp Test QA Merchant',
                                        'operand_3' => 70,
                                    ],
                                    'remarks' => [
                                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                        'match_percentage'      => 29,
                                        'required_percentage'   => 70,
                                    ],
                                ],
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 29,
                                'required_percentage'   => 70,
                            ],
                        ]
                    ]
                ],
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload, 'live');

        $bvsValidation = $this->getDbEntityById('bvs_validation', $bvsValidation['validation_id']);

        $verificationDetail = $this->getDbEntityById('merchant_detail', '1cXSLlUU8V9sXl');

        $this->assertEquals('success', $bvsValidation->getValidationStatus());

        $this->assertEquals('verified', $verificationDetail->getCinVerificationStatus());

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'cin', 'merchant_id' => $merchantId]);

        $this->assertEquals(["signatory_validation_status"=> "verified", 'bvs_validation_id'=>$bvsValidation['validation_id']],$merchantVerificationDetail['metadata']);

    }

    public function testCINSignatorySuccessExperimentNotLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
              'business_type' => 4,
            'cin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'cin', 'merchant_id' => $merchantId]);

        $this->assertNull($merchantVerificationDetail['metadata']);

        $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $this->assertEquals('verified', $merchantDetail['cin_verification_status']);



    }

    public function testCINSignatoryFailureExperimentLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'failure');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
              'business_type' => 4,
            'cin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'cin', 'merchant_id' => $merchantId]);

        $bvsValidation = $this->getDbLastEntity('bvs_validation', 'live');

        $this->assertEquals(["signatory_validation_status"=> "not_initiated", 'bvs_validation_id' => $bvsValidation['validation_id']],$merchantVerificationDetail['metadata']);

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'signatory_validation', 'merchant_id' => $merchantId]);

        $this->assertEquals('not_initiated',$merchantVerificationDetail['status']);

    }

    public function testLLPINSignatorySuccessExperimentLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
              'business_type' => 6,
            'cin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'llp_deed', 'merchant_id' => $merchantId]);

        $bvsValidation = $this->getDbLastEntity('bvs_validation', 'live');

        $this->assertEquals(["signatory_validation_status" => "verified", 'bvs_validation_id' => $bvsValidation['validation_id']],$merchantVerificationDetail['metadata']);

    }

    public function testLLPINSignatorySuccessExperimentNotLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
              'business_type' => 6,
            'cin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'llp_deed', 'merchant_id' => $merchantId]);

        $this->assertNull($merchantVerificationDetail['metadata']);

        $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $this->assertEquals('verified', $merchantDetail['cin_verification_status']);

    }

    public function testLLPINSignatoryFailureExperimentLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'failure');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
              'business_type' => 6,
            'cin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'llp_deed', 'merchant_id' => $merchantId]);

        $bvsValidation = $this->getDbLastEntity('bvs_validation', 'live');

        $this->assertEquals(["signatory_validation_status" =>"not_initiated", 'bvs_validation_id' => $bvsValidation['validation_id']],$merchantVerificationDetail['metadata']);

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'signatory_validation', 'merchant_id' => $merchantId]);

        $this->assertEquals('not_initiated',$merchantVerificationDetail['status']);

    }

    public function testGSTINSignatorySuccessExperimentLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
              'business_type' => 4,
            'gstin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'gstin', 'merchant_id' => $merchantId]);

        $bvsValidation = $this->getDbLastEntity('bvs_validation', 'live');

        $this->assertEquals(["signatory_validation_status" => "verified", 'bvs_validation_id' => $bvsValidation['validation_id']],$merchantVerificationDetail['metadata']);

    }

    public function testGSTINSignatorySuccessExperimentNotLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
            'business_type' => 4,
            'gstin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'gstin', 'merchant_id' => $merchantId]);

        $this->assertNull($merchantVerificationDetail['metadata']);

        $this->assertNull($merchantVerificationDetail['status']);
    }

    public function testGSTINSignatoryFailureExperimentLive()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => "1cXSLlUU8V9sXl",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'failure');
        Config::set('services.bvs.sync.flow', true);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId,
            'business_type' => 4,
            'gstin_verification_status'=>'pending']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'gstin', 'merchant_id' => $merchantId]);

        $bvsValidation = $this->getDbLastEntity('bvs_validation', 'live');

        $this->assertEquals(["signatory_validation_status"=> "not_initiated", "bvs_validation_id"=> $bvsValidation['validation_id']],$merchantVerificationDetail['metadata']);

        $merchantVerificationDetail = $this->getDbEntity('merchant_verification_detail', ['artefact_type' => 'signatory_validation', 'merchant_id' => $merchantId]);

        $this->assertEquals('not_initiated',$merchantVerificationDetail['status']);

    }

    public function testDeleteAdditionalWebsites()
    {
        $merchantId = $this->fixtures->create('merchant')->getId();

        $this->fixtures->create(
            'merchant_detail',
            [
                'merchant_id'         => $merchantId,
                'additional_websites' => [
                    'https://www.website1.com',
                    'https://www.website2.com',
                    'https://www.website3.com',
                    'https://www.website4.com',
                ],
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = "/merchant/$merchantId/websites";

        $this->ba->adminAuth();

        $this->startTest();

        $actualAdditionalWebsites = $this->getLastEntity('merchant_detail', true)['additional_websites'];

        $expectedAdditionalWebsites = [
            'https://www.website2.com',
            'https://www.website3.com',
        ];

        $this->assertEquals($expectedAdditionalWebsites, $actualAdditionalWebsites);
    }

    public function getCollectionOfWebsites(int $count): array
    {
        $websites = [];

        for ($i = 0; $i < $count; $i++)
        {
            $websites[] = ['http://webhook.com'];
        }

        return $websites;
    }

    public function testPutPreSignUpDetailsWithReferralCode()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $appType = MerchantApplications\Entity::REFERRED;

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => self::DEFAULT_MERCHANT_ID,
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => self::DEFAULT_MERCHANT_ID,
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $referrerId = self::DEFAULT_MERCHANT_ID;

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);

        $this->startTest();

        $merchantAcessMap = $this->getDbEntity('merchant_access_map',
                                               [
                                                   'merchant_id' => $referredSubMerchantId
                                               ], 'test')
                                 ->toArray();

        $referredSubMerchant = $this->getDbEntity('merchant', ['id' => $referredSubMerchantId]);

        $merchantApp = $this->getDbEntity('merchant_application', ['application_id' => $app->getId()]);


        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                        ->where('user_id', '=', $referrerId)
                        ->get();

        $this->assertEmpty($mapping);

        $this->assertEquals($merchantApp->type, $appType);

        $this->assertEquals($referredSubMerchant->tagNames(), array('Ref-' . $referrerId));

        $this->assertEquals($referredSubMerchant->getPricingPlanId(), self::DEFAULT_MERCHANT_ID);

        $this->assertSame($referredSubMerchantId, $merchantAcessMap['merchant_id']);

        $this->assertSame($referrerId, $merchantAcessMap['entity_owner_id']);

        $this->assertSame($app->getId(), $merchantAcessMap['entity_id']);
        $this->assertNotContains('MerchantUser01', $referredSubMerchant->users->getIds());
    }

    public function testPutPreSignupDetailsWithInvalidCapitalReferralCode()
    {
        $this->mockBvsService();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->fixtures->create('pricing:standard_plan');

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, ['pricing_plan_id' => '1hDYlICobzOCYt']);

        $referrerMerchantId = self::DEFAULT_MERCHANT_ID;

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals', ["product" => Constants\Product::CAPITAL]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);

        $this->startTest();

        $merchantAcessMap = $this->getDbEntity(
            'merchant_access_map',
            [
                'merchant_id' => $referredSubMerchantId
            ],
            'test'
        );

        $referredSubMerchant = $this->getDbEntity('merchant', ['id' => $referredSubMerchantId]);

        $merchantApp = $this->getDbEntity('merchant_application', ['application_id' => $app->getId()]);

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                     ->where('user_id', '=', $referrerMerchantId)
                     ->get();

        $this->assertEmpty($mapping);

        $this->assertEmpty($referredSubMerchant->tagNames());

        $this->assertEmpty($merchantAcessMap);
    }

    public function testPutPreSignupDetailsWithCapitalReferralCode()
    {
        $this->mockBvsService();

        $this->mockCapitalPartnershipSplitzExperiment();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $losServiceMock = \Mockery::mock('RZP\Services\LOSService', [$this->app])
                                  ->makePartial()
                                  ->shouldAllowMockingProtectedMethods();

        $this->app->instance('losService', $losServiceMock);

        $this->mockCreateApplicationRequestOnLOSService($losServiceMock);

        $this->mockGetProductsRequestOnLOSService($losServiceMock);

        $referrerMerchantId = self::DEFAULT_MERCHANT_ID;

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals', ["product" => Constants\Product::CAPITAL]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);

        $this->startTest();

        $merchantAcessMap = $this->getDbEntity('merchant_access_map',
                                               [
                                                   'merchant_id' => $referredSubMerchantId
                                               ], 'test')
                                 ->toArray();

        $referredSubMerchant = $this->getDbEntity('merchant', ['id' => $referredSubMerchantId]);

        $merchantApp = $this->getDbEntity('merchant_application', ['application_id' => $app->getId()]);


        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                     ->where('user_id', '=', $referrerMerchantId)
                     ->get();

        $this->assertEmpty($mapping);

        $this->assertEquals($merchantApp->type, MerchantApplications\Entity::REFERRED);

        $this->assertContains('Ref-' . $referrerMerchantId, $referredSubMerchant->tagNames());

        $this->assertSame($referredSubMerchantId, $merchantAcessMap['merchant_id']);

        $this->assertSame($referrerMerchantId, $merchantAcessMap['entity_owner_id']);

        $this->assertSame($app->getId(), $merchantAcessMap['entity_id']);
    }

    public function testPutPreSignUpDetailsWithBankingReferralCodeInX()
    {
        $this->mockBvsService();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $appType = MerchantApplications\Entity::REFERRED;

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => self::DEFAULT_MERCHANT_ID,
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => self::DEFAULT_MERCHANT_ID,
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $referrerId = self::DEFAULT_MERCHANT_ID;

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals', ["product" => Constants\Product::BANKING]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);

        $razorxMock = $this->getMockBuilder(Core::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $razorxMock->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        $this->mockSalesforceEventTracked('sendPartnerLeadInfo');

        $this->startTest();

        $merchantAcessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $referredSubMerchantId
            ], 'test')
            ->toArray();

        $referredSubMerchant = $this->getDbEntity('merchant', ['id' => $referredSubMerchantId]);

        $merchantApp = $this->getDbEntity('merchant_application', ['application_id' => $app->getId()]);


        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
            ->where('user_id', '=', $referrerId)
            ->get();

        $this->assertEmpty($mapping);

        $this->assertEquals($merchantApp->type, $appType);

        $this->assertEquals($referredSubMerchant->tagNames(), array('Ref-' . $referrerId));

        $this->assertEquals($referredSubMerchant->getPricingPlanId(), self::DEFAULT_MERCHANT_ID);

        $this->assertSame($referredSubMerchantId, $merchantAcessMap['merchant_id']);

        $this->assertSame($referrerId, $merchantAcessMap['entity_owner_id']);

        $this->assertSame($app->getId(), $merchantAcessMap['entity_id']);
    }

    public function testPutPreSignUpDetailsWithPrimaryReferralCodeInX()
    {
        $this->mockBvsService();

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $this->fixtures->user->createBankingUserForMerchant(self::DEFAULT_SUBMERCHANT_ID, [], 'owner', 'live');

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => self::DEFAULT_MERCHANT_ID,
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => self::DEFAULT_MERCHANT_ID,
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals', ["product" => Constants\Product::PRIMARY]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);

        $this->startTest();

        $referredSubMerchant = $this->getDbEntity('merchant', ['id' => $referredSubMerchantId]);

        $this->assertEquals([], $referredSubMerchant->tagNames());

        $this->assertNull($referredSubMerchant->getPricingPlanId());
    }

    public function testPutPreSignUpDetailsWithPrimaryReferralCodeInXForAggregator()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => self::DEFAULT_MERCHANT_ID,
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => self::DEFAULT_MERCHANT_ID,
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals', ["product" => Constants\Product::BANKING]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);

        $this->startTest();

        $referredSubMerchant = $this->getDbEntity('merchant', ['id' => $referredSubMerchantId]);

        $this->assertNotContains('MerchantUser01', $referredSubMerchant->users->getIds());
    }

    public function testPutPreSignUpDetailsWithReferralCodeForAggregator()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $managedApp = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);

        $appType = MerchantApplications\Entity::MANAGED;

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => self::DEFAULT_MERCHANT_ID,
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => self::DEFAULT_MERCHANT_ID,
            'entity_id'       => $managedApp->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $referrerId = self::DEFAULT_MERCHANT_ID;

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);

        $this->startTest();

        $merchantAcessMap = $this->getDbEntity('merchant_access_map',
            [
                'merchant_id' => $referredSubMerchantId
            ], 'test')
            ->toArray();

        $referredSubMerchant = $this->getDbEntity('merchant', ['id' => $referredSubMerchantId]);

        $merchantApp = $this->getDbEntity('merchant_application', ['application_id' => $managedApp->getId()]);

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                       ->where('user_id', '=', $referrerId)
                       ->get();

        $this->assertEmpty($mapping);

        $this->assertEquals($merchantApp->type, $appType);

        $this->assertEquals($referredSubMerchant->tagNames(), array('Ref-' . $referrerId));

        $this->assertEquals($referredSubMerchant->getPricingPlanId(), self::DEFAULT_MERCHANT_ID);

        $this->assertSame($referredSubMerchantId, $merchantAcessMap['merchant_id']);

        $this->assertSame($referrerId, $merchantAcessMap['entity_owner_id']);

        $this->assertSame($managedApp->getId(), $merchantAcessMap['entity_id']);
        $this->assertContains('MerchantUser01', $referredSubMerchant->users->getIds());
    }

    /**
     * This testcase validates the following
     * 1. Add referral code when subM sign up from referral link
     * 2. attaches managed app
     * 3. adds partner user to subM users list with a role
     * 4. validates if the role above attached for banking account is Role::VIEW_ONLY
     */
    public function testPutPreSignUpDetailsWithBankingReferralCodeInXForAggregator()
    {
        $this->mockBvsService();

        $testData = $this->testData['testPutPreSignUpDetailsWithPrimaryReferralCodeInXForAggregator'];

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);

        $this->fixtures->create('pricing:two_percent_pricing_plan', [
            'plan_id' => self::DEFAULT_MERCHANT_ID,
            'type'    => 'pricing',
        ]);

        $configAttributes = [
            'default_plan_id' => self::DEFAULT_MERCHANT_ID,
            'entity_id'       => $app->getId(),
            'entity_type'     => 'application',
        ];

        $this->fixtures->create('partner_config', $configAttributes);

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals', ["product" => Constants\Product::BANKING]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($referredSubMerchantId);

        $this->ba->proxyAuth('rzp_test_' . $referredSubMerchantId, $merchantUser['id']);
        $this->ba->addXOriginHeader();

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => 'enable', ]]]);

        $this->startTest($testData);

        $mapping = $this->fixtures->user->getMerchantUserMapping($referredSubMerchantId, 'MerchantUser01', 'banking');

        $this->assertEquals($mapping->first()->role, Role::VIEW_ONLY);
    }

    public function testPutPreSignUpDetailsWithInvalidReferralCode()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $referredSubMerchant = $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID);

        $this->fixtures->merchant->createDummyPartnerApp();

        $referredSubMerchantId = self::DEFAULT_SUBMERCHANT_ID;

        $this->fixtures->create('referrals');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $referredSubMerchantId,
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($referredSubMerchantId, [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_' . $referredSubMerchantId, $merchantUser['id']);

        $this->startTest();

        $merchantAcessMap = $this->getDbEntity('merchant_access_map',
                                               [
                                                   'merchant_id' => $referredSubMerchantId
                                               ], 'live');

        $this->assertSame(null, $merchantAcessMap);

        $this->assertEmpty($referredSubMerchant->tagNames());
    }

    public function testGetMerchantDetailsWithBalanceConfigs()
    {
        $merchant = $this->fixtures->create('merchant', ['id'=>'100ghi000ghi00']);

        $balanceData1 = [
            'id'                => '100abc000abc00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'banking',
            'currency'          => 'INR',
            'name'              => null,
            'balance'           => 0,
            'credits'           => 0,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => '2224440041626905',
            'account_type'      => null,
            'channel'           => null,
            'updated_at'        => 1
        ];

        $balanceData2 = [
            'id'                => '100def000def00',
            'merchant_id'       => '100ghi000ghi00',
            'type'              => 'primary',
            'currency'          => null,
            'name'              => null,
            'balance'           => 100000,
            'credits'           => 50000,
            'fee_credits'       => 0,
            'refund_credits'    => 0,
            'account_number'    => null,
            'account_type'      => null,
            'channel'           => 'shared',
            'updated_at'        => 1
        ];

        $this->fixtures->create('balance',$balanceData1);

        $this->fixtures->create('balance',$balanceData2);

        $this->fixtures->create('balance_config',
            [
                'id'                            =>  '100yz000yz00yz',
                'balance_id'                    =>  '100def000def00',
                'type'                          =>  'primary',
                'negative_transaction_flows'   =>  ['refund'],
                'negative_limit_auto'           =>  5000000,
                'negative_limit_manual'         =>  5000000
            ]
        );

        $this->fixtures->create('balance_config',
            [
                'id'                            =>  '100ab000ab00ab',
                'balance_id'                    =>  '100abc000abc00',
                'type'                          =>  'banking',
                'negative_transaction_flows'   =>  ['payout'],
                'negative_limit_auto'           =>  5000000,
                'negative_limit_manual'         =>  5000000
            ]
        );

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();
        $this->ba->addAccountAuth($merchant->getId());

        $this->startTest();
    }

    public function testGetMerchantDetailsRegisteredBusinessWithSelectiveRequiredFields()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            MerchantDetails::MERCHANT_ID => $merchant['id'],
            MerchantDetails::BUSINESS_TYPE => '1',
            MerchantDetails::BUSINESS_CATEGORY => BusinessCategory::FINANCIAL_SERVICES,
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::MUTUAL_FUND,
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testGetMerchantDetailsRegisteredBusinessWithOptionalFields()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            MerchantDetails::MERCHANT_ID => $merchant['id'],
            MerchantDetails::BUSINESS_TYPE => '1',
            MerchantDetails::BUSINESS_CATEGORY => BusinessCategory::TOURS_AND_TRAVEL,
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::AVIATION,
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testGetMerchantDetailsRegisteredBusinessNgo()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            MerchantDetails::MERCHANT_ID => $merchant['id'],
            MerchantDetails::BUSINESS_TYPE => '7',
            MerchantDetails::BUSINESS_CATEGORY => BusinessCategory::EDUCATION,
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::SCHOOLS,
        ]);

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testCompanyPanVerificationBusinessNameUpdateSuccess()
    {
        $this->markTestSkipped("Skipping becoz company pan verification is moved to BVS");
        $test = 'testCompanyPanVerificationBusinessNameUpdate';

        $data = [
            'mock_status'                  => 'success',
            'previous_verification_status' => 'failed',
            'new_verification_status'      => 'verified',
            'business_name'                => 'xyz',
        ];

        $this->companyPanVerificationAutoKyc($test, $data);
    }

    public function testCompanyPanVerificationBusinessNameUpdateFailed()
    {
        $this->markTestSkipped("Skipping becoz company pan verification is moved to BVS");
        $test = 'testCompanyPanVerificationBusinessNameUpdate';

        $data = [
            'mock_status'                  => 'failure',
            'previous_verification_status' => 'verified',
            'new_verification_status'      => 'failed',
            'business_name'                => 'xyz',
        ];

        $this->companyPanVerificationAutoKyc($test, $data);
    }

    public function testCompanyPanVerificationCompanyPanUpdateSuccess()
    {
        $this->markTestSkipped("Skipping becoz company pan verification is moved to BVS");
        $test = 'testCompanyPanVerificationCompanyPanUpdate';

        $data = [
            'mock_status'                  => 'success',
            'previous_verification_status' => 'failed',
            'new_verification_status'      => 'verified',
            'company_pan'                  => 'AAACA1234J',
        ];

        $this->companyPanVerificationAutoKyc($test, $data);
    }

    public function testCompanyPanVerificationCompanyPanUpdateFailed()
    {
        $this->markTestSkipped("Skipping becoz company pan verification is moved to BVS");
        $test = 'testCompanyPanVerificationCompanyPanUpdate';

        $data = [
            'mock_status'                  => 'failure',
            'previous_verification_status' => 'verified',
            'new_verification_status'      => 'failed',
            'company_pan'                  => 'AAACA1234J',
        ];

        $this->companyPanVerificationAutoKyc($test, $data);
    }

    protected function companyPanVerificationAutoKyc(string $test, array $data)
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'                     => $merchantId,
            'company_pan_verification_status' => $data['previous_verification_status'],
            'business_name'                   => $data['business_name'] ?? 'Test123',
            'company_pan'                     => 'AAACA1234J',
            'business_type'                   => '4',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        Config::set('applications.kyc.company_pan_authentication', $data['mock_status']);

        Config::set('applications.kyc.pan_authentication', $data['mock_status']);

        Config::set('applications.kyc.mock', true);

        $testData = $this->testData[$test];

        $this->startTest($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails[Entity::COMPANY_PAN_VERIFICATION_STATUS], $data['new_verification_status']);
    }

    public function testPromoterPanVerificationPromoterPanNameUpdateSuccess()
    {
        $this->markTestSkipped("Skipping becoz personal pan verification is moved to BVS");
        $test = 'testPromoterPanVerificationPromoterPanNameUpdate';

        $data = [
            'mock_status'                  => 'success',
            'previous_verification_status' => 'failed',
            'new_verification_status'      => 'verified',
            'promoter_pan_name'            => 'xyz',
        ];

        $this->PromoterPanVerificationAutoKyc($test, $data);
    }

    public function testPromoterPanVerificationPromoterPanNameUpdateFailed()
    {
        $this->markTestSkipped("Skipping becoz personal pan verification is moved to BVS");
        $test = 'testPromoterPanVerificationPromoterPanNameUpdate';

        $data = [
            'mock_status'                  => 'failure',
            'previous_verification_status' => 'verified',
            'new_verification_status'      => 'failed',
            'promoter_pan_name'            => 'xyz',
        ];

        $this->PromoterPanVerificationAutoKyc($test, $data);
    }

    public function testPromoterPanVerificationPromoterPanUpdateSuccess()
    {
        $this->markTestSkipped("Skipping becoz personal pan verification is moved to BVS");
        $test = 'testPromoterPanVerificationPromoterPanUpdate';

        $data = [
            'mock_status'                  => 'success',
            'previous_verification_status' => 'failed',
            'new_verification_status'      => 'verified',
        ];

        $this->PromoterPanVerificationAutoKyc($test, $data);
    }

    public function testPromoterPanVerificationPromoterPanUpdateFailed()
    {
        $this->markTestSkipped("Skipping becoz personal pan verification is moved to BVS");
        $test = 'testPromoterPanVerificationPromoterPanUpdate';

        $data = [
            'mock_status'                  => 'failure',
            'previous_verification_status' => 'verified',
            'new_verification_status'      => 'failed',
        ];

        $this->PromoterPanVerificationAutoKyc($test, $data);
    }

    protected function PromoterPanVerificationAutoKyc(string $test, array $data)
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->create('merchant_detail', [
            'merchant_id'             => $merchantId,
            'poi_verification_status' => $data['previous_verification_status'],
            'promoter_pan_name'       => $data['promoter_pan_name'] ?? 'Test123',
            'promoter_pan'            => 'AAAPA1234J',
            'business_type'           => '4',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        Config::set('applications.kyc.pan_authentication', $data['mock_status']);

        Config::set('applications.kyc.mock', true);

        $testData = $this->testData[$test];

        $this->runRequestResponseFlow($testData);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails[Entity::POI_VERIFICATION_STATUS], $data['new_verification_status']);
    }

    public function testCanSubmitAutoKycVerificationStatusCorrectDetails()
    {
        $input = [
            'poi_verification_status'         => 'failed',
            'company_pan_verification_status' => 'verified',
            'gstin_verification_status'       => 'verified',
            ];

        $this->checkCanSubmitForAutoKycVerificationStatus($input, 'testSubmit');
    }

    public function testVerifyBvsTriggerPostFormSubmissionForPersonalPanOcr()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = [
            'personal_pan_doc_verification_status' => 'pending',
            'business_type'                        => '1',
            'merchant_id'                          => $mid,
        ];

        $this->mockRazorX('testSubmit', 'bvs_personal_pan_ocr', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'personal_pan',
                                                      'validation_unit' => 'proof'
                                                  ]);
    }

    public function testVerifyBvsTriggerPostFormSubmissionForBusinessPanOcr()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = [
            'company_pan_doc_verification_status' => 'pending',
            'business_type'                       => '4',
            'merchant_id'                         => $mid,
        ];

        $this->mockRazorX('testSubmit', 'bvs_business_pan_ocr', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'business_pan',
                                                      'validation_unit' => 'proof'
                                                  ]);
    }

    public function testVerifyBvsTriggerPostFormSubmissionForCin()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = [
            'business_type' => '4',
            'merchant_id'   => $mid,
        ];

        $this->mockRazorX('testSubmit', 'bvs_cin_validation', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'cin',
                                                      'validation_unit' => 'identifier'
                                                  ]);
    }

    public function testVerifyBvsTriggerPostFormSubmissionForGstin()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = [
            'business_type' => '1',
            'merchant_id'   => $mid,
        ];

        $this->mockRazorX('testSubmit', 'bvs_gstin_validation', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'gstin',
                                                      'validation_unit' => 'identifier'
                                                  ]);
    }

    public function testVerifyBvsTriggerPostFormSubmissionForLlpin()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = [
            'business_type' => '6',
            'merchant_id'   => $mid,
        ];

        $this->mockRazorX('testSubmit', 'bvs_cin_validation', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'llp_deed',
                                                      'validation_unit' => 'identifier'
                                                  ]);
    }

    public function testVerifyBvsTriggerPostFormSubmissionForCancelledChequeOcr()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = [
            'bank_details_doc_verification_status' => 'pending',
            'merchant_id'                          => $mid,
        ];

        $this->mockRazorX('testSubmit', 'bvs_cancelled_cheque_ocr', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'bank_account',
                                                      'validation_unit' => 'proof'
                                                  ]);
    }

    public function testVerifyBvsTriggerPostFormSubmissionForShopEstbNumber()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = [
            'shop_establishment_verification_status' => 'pending',
            'merchant_id'                          => $mid,
            'business_registered_state'            => "DL",
            'shop_establishment_number'            => "shopNum1234",
        ];

        $this->mockRazorX('testSubmit', 'bvs_shop_estb_auth', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'shop_establishment',
                                                      'validation_unit' => 'identifier'
                                                  ]);
    }

    public function testVerifyBvsTriggerPostFormSubmissionForBankDetails()
    {
        $mid = '1cXSLlUU8V9sXl';

        $input = ['merchant_id'               => $mid,
                  'gstin_verification_status' => 'verified',
        ];

        $this->mockRazorX('testSubmit', 'bvs_penny_testing', 'on');

        $this->submitL2FormAndVerifyBvsValidation($input,
                                                  $mid,
                                                  [
                                                      'artefact_type'   => 'bank_account',
                                                      'validation_unit' => 'identifier'
                                                  ]);
    }

    /**
     * @param array  $input
     * @param string $mid
     * @param array  $validationInput
     */
    protected function submitL2FormAndVerifyBvsValidation(array $input, string $mid, array $validationInput)
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        Mail::fake();

        $this->checkCanSubmitForAutoKycVerificationStatus($input, 'testSubmit');

        $bvsValidation = $this->getDbEntity('bvs_validation', ['owner_id' => $mid, 'owner_type' => 'merchant','artefact_type'=>$validationInput['artefact_type'],'validation_unit' => $validationInput['validation_unit']]);

        $this->assertNotNull($bvsValidation);

        $expectedValidationValues = [
            'artefact_type'     => $validationInput['artefact_type'],
            'owner_id'          => $mid,
            'owner_type'        => 'merchant',
            'platform'          => 'pg',
            'validation_status' => 'captured',
            'validation_unit'   => $validationInput['validation_unit'],
        ];

        (new BvsValidationTest())->validateSuccessBvsValidation($bvsValidation, $expectedValidationValues);
    }

    protected function checkCanSubmitForAutoKycVerificationStatus(array $input, string $test)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', $input);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      [
                                          'address_proof_url',
                                          'business_pan_url',
                                          'business_proof_url',
                                          'promoter_address_url',
                                          'personal_pan',
                                          'cancelled_cheque',
                                      ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->createBalanceForSharedMerchant();

        $testdata = $this->testData[$test];

        $this->startTest($testdata);
    }

    public function testCanSubmitAutoKycVerificationStatusGstinIncorrect()
    {
        $input = [
            'poi_verification_status'         => 'verified',
            'company_pan_verification_status' => 'verified',
            'gstin_verification_status'       => 'incorrect_details',
            'business_type'                   => '1'
        ];

        $this->checkCanSubmitForAutoKycVerificationStatus($input, 'testSubmit');
    }

    protected function createMerchantDataForGstinSelfServe($merchantCreated = false)
    {
        if ($merchantCreated === true)
        {
            $this->fixtures->edit('merchant', '10000000000000', [
                'activated'    => 1,
                'activated_at' => Carbon::now(Timezone::IST)->timestamp,
                'invoice_code' => 'hello1234567',
            ]);

            $this->fixtures->on('live')->create('methods:default_methods', [
                'merchant_id' => '1cXSLlUU8V9sXl'
            ]);

            $this->fixtures->create('merchant_detail', [
                'merchant_id'                 => '10000000000000',
                'promoter_pan_name'           => 'randomLegalName',
                'gstin'                       => 'abcdefghijklmno',
                'business_name'               => 'randomTradeName',
                'business_registered_address' => '1302, 13, Test, 18 B G KHER ROAD',
                'business_registered_pin'     => '451111',
                'business_registered_city'    => 'Pune',
                'business_registered_state'   => 'MP'
            ]);

            $this->fixtures->create(
                'payout',
                [
                    'channel'         => 'icici',
                    'amount'          => 1000,
                    'pricing_rule_id' => '1nvp2XPMmaRLxb',
                ]);

            $this->ba->privateAuth();

            // NB payment
            $this->fixtures->create('terminal:shared_netbanking_pnb_terminal');
        }

        // Card payment less than 2k
        $p1 = $this->getDefaultPaymentArray();

        $p1['amount'] = 50000;

        $p1 = $this->doAuthAndCapturePayment();

        $this->fixtures->edit('payment', $p1['id'], [
            'captured_at' => Carbon::now(Timezone::IST)->timestamp + 5,
        ]);

        // Card payment greater than 2k
        $p2 = $this->getDefaultPaymentArray();

        $p2['amount'] = 234000;

        $p2 = $this->doAuthAndCapturePayment($p2);

        $this->fixtures->edit('payment', $p2['id'], [
            'captured_at' => Carbon::now(Timezone::IST)->timestamp + 5,
        ]);
    }

    protected function createMerchantAndInvoiceData()
    {
        $oldDateTime = Carbon::create(2018, 1, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($oldDateTime);

        $this->createMerchantDataForGstinSelfServe();

        $newDateTime = Carbon::create(2018, 2, 27, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($newDateTime);

        $this->createMerchantDataForGstinSelfServe(true);

        Carbon::setTestNow();

        $this->ba->cronAuth();

        $newDateTime = Carbon::create(2018, 2, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($newDateTime);

        $request = [
            'url'    => '/merchants/invoice/create',
            'method' => 'POST',
        ];

        $this->makeRequestAndGetContent($request);

        $currentTime = Carbon::create(2018, 3, 1, 12, 0, 0, Timezone::IST);

        Carbon::setTestNow($currentTime);

        $this->makeRequestAndGetContent($request);

        $this->fixtures->merchant->addFeatures('gstin_self_serve');

        $user = $this->fixtures->create('user');

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_' . '10000000000000', $user['id']);

        Config::set('services.bvs.mock', true);
    }

    public function testUpdateGstinSelfServeWithGSTInvoices()
    {
        //TODO : Testcase has to be fixed
        $this->markTestSkipped("Skipping Testcase, Need to be fixed");

        $this->createMerchantAndInvoiceData();

        $response = $this->initiateGstinSelfServe();

        $this->assertEquals(false, $response['sync_flow']);

        $this->assertEquals(false, $response['workflow_created']);

        $this->setBvsValidationDetailForGstinUpdateSelfServe();

        $this->assertGstinSelfServeStatusAndRejectionReason([
                                                                'workflow_exists'          => false,
                                                                'request_under_validation' => true
                                                            ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Edit gstin bvs result", "Invoices create result"], true));
                    }));

        $this->processBvsResponseForGstinSelfServe();

        $this->assertCacheDataNullForGstinSelfServe('10000000000000');

        $entities = $this->getEntities('merchant_invoice', [], true);

        $this->assertEquals(5, $entities['count']);

        $file = $this->getLastEntity('file_store', true);

        $this->assertEquals('10000000000000', $file['merchant_id']);

        $this->assertEquals('merchant_pg_invoices/2018/2/10000000000000', $file['name']);
    }

    public function testUpdateGstinSelfServe()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  false
        ]);

        $this->startTest();

        $this->assertCacheDataForGstinSelfServe($merchant['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  true
        ]);
    }

    public function testAddGstinSelfServeValidationFailWorkflowApprove()
    {
        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        extract($this->setupMerchantForGstinSelfServeTest());

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  false
        ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(4))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Add gstin bvs result", "Add gstin workflow created", "Add gstin workflow status", "Self Serve Success"], true));
                    }));

        $this->mockStorkForAddGstinValidationFailWorkflowApprove();

        $this->setupWorkflow('edit_gstin_details', 'edit_merchant_gstin_detail');

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->startTest();

        $this->processBvsResponseForGstinSelfServe('failed');

        $this->assertCacheDataNullForGstinSelfServe($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->esClient->indices()->refresh();

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'workflow_status'          =>  'open',
            'needs_clarification'      =>  null,
            'request_under_validation' =>  false,
            'permission'               => 'edit_merchant_gstin_detail'
        ]);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertWorkflowDataForGstInSelfServe($workflowAction);

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertArraySelectiveEquals([
            'gstin'                       => '18AABCU9603R1ZM',
        ], $merchantDetail);

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals('emails.merchant.gstin_updated_on_workflow_approve', $mail->view);

            $this->assertEquals('18AABCU9603R1ZM', $mail->viewData['gstin']);

            $this->assertEquals('added', $mail->viewData['gstin_operation']);

            return true;
        });

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'workflow_status'          =>  'executed',
            'needs_clarification'      =>  null,
            'request_under_validation' =>  false
        ]);
    }

    protected function mockStorkForAddGstinValidationFailWorkflowApprove()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'gstin'  => '18AABCU9603R1ZM'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_add_gstin_workflow_approve', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hi,
GSTIN has been added successfully to your Razorpay Account
GSTIN: 18AABCU9603R1ZM
Cheers,
Team Razorpay',
            '1234567890'
        );
    }

    public function testUpdateGstinSelfServeValidationFailWorkflowApprove()
    {
        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        extract($this->setupMerchantForGstinSelfServeTest(false));

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  false
        ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(4))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Edit gstin bvs result", "Edit gstin workflow created", "Edit gstin workflow status", "Self Serve Success"], true));
                    }));

        $this->mockStorkForUpdateGstWorkflowApprove();

        $this->setupWorkflow('edit_gstin_details', 'update_merchant_gstin_detail');

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->startTest();

        $this->processBvsResponseForGstinSelfServe('failed');

        $this->assertCacheDataNullForGstinSelfServe($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->esClient->indices()->refresh();

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'workflow_status'          =>  'open',
            'needs_clarification'      =>  null,
            'request_under_validation' =>  false,
            'permission'               => 'update_merchant_gstin_detail'
        ]);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertWorkflowDataForGstInSelfServe($workflowAction, false);

        $this->performWorkflowAction($workflowAction['id'], true);

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertArraySelectiveEquals([
            'gstin'                       => '18AABCU9603R1ZM',
        ], $merchantDetail);

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals('emails.merchant.gstin_updated_on_workflow_approve', $mail->view);

            $this->assertEquals('18AABCU9603R1ZM', $mail->viewData['gstin']);

            $this->assertEquals('updated', $mail->viewData['gstin_operation']);

            return true;
        });

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'workflow_status'          =>  'executed',
            'needs_clarification'      =>  null,
            'request_under_validation' =>  false
        ]);
    }

    protected function mockStorkForUpdateGstWorkflowApprove()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'gstin'    => '18AABCU9603R1ZM'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_gstin_workflow_approve', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hey,
Your GSTIN has been updated successfully to
GSTIN: 18AABCU9603R1ZM
Cheers,
Team Razorpay',
            '1234567890'
        );
    }

    public function testAddGstinSelfServeValidationFailWorkflowReject()
    {
        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        extract($this->setupMerchantForGstinSelfServeTest());

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'needs_clarification'      =>  null,
            'request_under_validation' =>  false
        ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(3))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Add gstin bvs result", "Add gstin workflow created", "Add gstin workflow status"], true));
                    }));

        $this->mockStorkForAddGstinSelfServeValidationFailWorkflowReject();

        $this->setupWorkflow('edit_gstin_details', 'edit_merchant_gstin_detail');

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->startTest();

        $this->processBvsResponseForGstinSelfServe('failed');

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertWorkflowDataForGstInSelfServe($workflowAction);

        $this->rejectWorkFlowWithRejectionReason($workflowAction['id']);

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertCacheDataNullForGstinSelfServe($merchant['id']);

        $this->assertArraySelectiveEquals([
            'gstin'                       => null,
        ], $merchantDetail);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('Test body', $data['messageBody']);

            $this->assertEquals('emails.merchant.rejection_reason_notification', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'needs_clarification'      =>  null,
            'workflow_status'          => 'rejected',
            'rejection_reason_message' => 'Test body'
        ]);
    }

    protected function mockStorkForAddGstinSelfServeValidationFailWorkflowReject()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'merchant_name'  => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_add_gstin_rejection', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hi Test name, Your request for adding the GSTIN to Razorpay account has been rejected. Please click on https://dashboard.razorpay.com/app/profile/rejection_update_gstin to know more.
-Team Razorpay',
            '1234567890'
        );
    }

    public function testUpdateGstinSelfServeValidationFailWorkflowReject()
    {
        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        extract($this->setupMerchantForGstinSelfServeTest(false));

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'needs_clarification'      =>  null,
            'request_under_validation' =>  false
        ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(3))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, ["Edit gstin bvs result", "Edit gstin workflow created", "Edit gstin workflow status"], true));
                    }));

        $this->mockStorkForUpdateGstinRejectionReason();

        $this->setupWorkflow('edit_gstin_details', 'update_merchant_gstin_detail');

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->startTest();

        $this->processBvsResponseForGstinSelfServe('failed');

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertWorkflowDataForGstInSelfServe($workflowAction, false);

        $this->rejectWorkFlowWithRejectionReason($workflowAction['id']);

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertCacheDataNullForGstinSelfServe($merchant['id']);

        $this->assertArraySelectiveEquals([
            'gstin'                       => 'abcdefghijklmno',
        ], $merchantDetail);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('Test body', $data['messageBody']);

            $this->assertEquals('emails.merchant.rejection_reason_notification', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'needs_clarification'      =>  null,
            'workflow_status'          => 'rejected',
            'rejection_reason_message' => 'Test body'
        ]);
    }

    protected function mockStorkForUpdateGstinRejectionReason()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'merchant_name' => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_gstin_rejection', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
                                          'Hi Test name, Your request for updating the GSTIN has been rejected. Please click on https://dashboard.razorpay.com/app/profile/rejection_update_gstin to know more.
-Team Razorpay',
                                          '1234567890'
        );
    }

    public function testUpdateGstinSelfServeValidationPassDeleteOldRejectionReason()
    {
        Config(['services.bvs.mock' => true]);

        extract($this->setupMerchantForGstinSelfServeTest());

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        $this->setupWorkflow('edit_gstin_details', 'edit_merchant_gstin_detail');

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->startTest();

        $this->processBvsResponseForGstinSelfServe('failed');

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->rejectWorkFlowWithRejectionReason($workflowAction['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'needs_clarification'      =>  null,
            'workflow_status'          => 'rejected',
            'rejection_reason_message' => 'Test body'
        ]);

        $this->setBvsValidationDetailForGstinUpdateSelfServe();

        $this->startTest();

        $this->processBvsResponseForGstinSelfServe();

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->esClient->indices()->refresh();

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  true,
            'needs_clarification'      =>  null,
            'workflow_status'          => 'rejected',
        ]);
    }

    public function testUpdateGstinSelfServeWhenInProgressShouldFail()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->startTest();

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  true
        ]);

        // changing the content and asserting that the data in cache didnt get over-written(changes to address, state and pin)

        $this->testData[__FUNCTION__]['request']['content'] = [
            'gstin'                       => '18AABCU9603R1ZN',
        ];
        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

       $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('already in progress');

        $this->startTest();

        // asserting only data created in first call is stored
        $this->assertCacheDataForGstinSelfServe($merchant['id']);
    }

    public function testUpdateGstinSelfServeInvalidUserRole()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $invalidRoles = [
            'manager',
            'operations',
            'finance',
            'support',
            'sellerapp',
            'linked_account_owner',
            'linked_account_admin',
            'rbl_supervisor',
            'rbl_agent',
            'view_only',
            'auth_link_supervisor',
            'auth_link_agent',
        ];

        foreach ($invalidRoles as $invalidRole)
        {
            $user = $this->fixtures->create('user');

            $mappingData = [
                'user_id'     => $user['id'],
                'merchant_id' => $merchant['id'],
                'role'        => $invalidRole,
            ];

            $this->fixtures->create('user:user_merchant_mapping', $mappingData);

            $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

            $this->startTest();
        }
    }

    public function testUpdateGstinSelfServeBvsFlow()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $response = $this->initiateGstinSelfServe();

        $this->assertEquals(false, $response['sync_flow']);

        $this->assertEquals(null, $response['workflow_created']);

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $requestToBvs = $this->app['cache']->get('unittest_bvs_validation_array');

        $artefactToBvs = $requestToBvs['artefact'];

        $rulesListToBvs = $requestToBvs['rules']['rules_list'];

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsProcessValidationHandler = $this->app['cache']->get('bvs_validation_custom_process_validation_' . $bvsValidationEntity['validation_id']);

        $this->assertArraySelectiveEquals([
            'gstin'                       => null,
            'business_registered_address' => '1302, 13, Test, 18 B G KHER ROAD',
            'business_registered_state'   => 'MP',
            'business_registered_city'    => 'Pune',
            'business_registered_pin'     => '451111',
            ], $merchantDetail); // asserting old values still present


        $this->assertArraySelectiveEquals([
            'owner_id'   => $merchant['id'],
            'owner_type' => 'merchant',
            'type'       => 'gstin',
            'details'    => [
                'gstin'            => '18AABCU9603R1ZM',
                'legal_name'       => 'randomLegalName',
                'trade_name'       => 'randomTradeName',
            ],
        ], $artefactToBvs);

        $this->assertCount(2, $rulesListToBvs);

        $this->assertArraySelectiveEquals([
            'owner_id'          => $merchant['id'],
            'owner_type'        => 'merchant',
            'validation_status' => 'captured',
            'artefact_type'     => 'gstin',
        ], $bvsValidationEntity);

        $this->assertEquals('GstinSelfServeCallbackHandler', $bvsProcessValidationHandler);
    }

    public function testUpdateGstinSelfServeBvsValidationCreationError()
    {
        $this->setupMerchantForGstinSelfServeTest();

        Config::set('services.bvs.response', 'failure');

        $this->expectException(ServerErrorException::class);

        $this->initiateGstinSelfServe();
    }

    public function testAddGstinSelfServeBvsValidationSuccess()
    {
        extract($this->setupMerchantForGstinSelfServeTest());

        $response = $this->initiateGstinSelfServe();

        $this->assertEquals(false, $response['sync_flow']);

        $this->assertEquals(false, $response['workflow_created']);

        $this->setBvsValidationDetailForGstinUpdateSelfServe();

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  true
        ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
            ->method('pushIdentifyAndTrackEvent')
            ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                $this->assertNotNull($properties);
                $this->assertTrue(in_array($eventName, ["Add gstin bvs result", "Self Serve Success"], true));
            }));

        $this->mockStorkForAddGstinSelfServeBvsValidationSuccess();

        $this->processBvsResponseForGstinSelfServe();

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertCacheDataNullForGstinSelfServe($merchant['id']);

        $this->assertArraySelectiveEquals([
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
            'business_registered_pin'     => '400018',
            'business_registered_city'    => 'Mumbai City',
            'business_registered_state'   => 'MH'
        ], $merchantDetail);

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals('emails.merchant.gstin_updated_self_serve', $mail->view);

            $this->assertArraySelectiveEquals([
                'gstin'                       => '18AABCU9603R1ZM',
                'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
                'business_registered_pin'     => '400018',
                'business_registered_city'    => 'Mumbai City',
                'business_registered_state'   => 'MH',
                'gstin_operation'             => 'added'
            ], $mail->viewData);

            return true;
        });

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  false
        ]);
    }

    protected function mockStorkForAddGstinSelfServeBvsValidationSuccess()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
            'business_registered_pin'     => '400018',
            'business_registered_city'    => 'Mumbai City',
            'business_registered_state'   => 'MH'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_add_gstin_auto_update_V1', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hi,

GSTIN has been added successfully to your Razorpay Account. The details are provided below.
GSTIN: 18AABCU9603R1ZM

Your registered address is updated as below, as per your GSTIN certificate
Registered address: 1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI, 400018, Mumbai City, MH

Cheers,
Team Razorpay',
            '1234567890'
        );
    }

    public function testUpdateGstinSelfServeBvsValidationSuccess()
    {
        extract($this->setupMerchantForGstinSelfServeTest(false));

        $response = $this->initiateGstinSelfServe();

        $this->assertEquals(false, $response['sync_flow']);

        $this->assertEquals(false, $response['workflow_created']);

        $this->setBvsValidationDetailForGstinUpdateSelfServe();

        $this->mockStorkForUpdateGstBvsValidationSuccess();

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  true
        ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
            ->method('pushIdentifyAndTrackEvent')
            ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                $this->assertNotNull($properties);
                $this->assertTrue(in_array($eventName, ["Edit gstin bvs result", "Self Serve Success"], true));
            }));

        $this->processBvsResponseForGstinSelfServe();

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertCacheDataNullForGstinSelfServe($merchant['id']);

        $this->assertArraySelectiveEquals([
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
            'business_registered_pin'     => '400018',
            'business_registered_city'    => 'Mumbai City',
            'business_registered_state'   => 'MH'
        ], $merchantDetail);

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals('emails.merchant.gstin_updated_self_serve', $mail->view);

            $this->assertArraySelectiveEquals([
                'gstin'                       => '18AABCU9603R1ZM',
                'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
                'business_registered_pin'     => '400018',
                'business_registered_city'    => 'Mumbai City',
                'business_registered_state'   => 'MH',
                'gstin_operation'             => 'updated'
            ], $mail->viewData);

            return true;
        });

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  false
        ]);
    }

    public function testUpdateGstinSelfServeSyncBvsValidationSuccess()
    {
        extract($this->setupMerchantForGstinSelfServeTest(false));

        $this->enableRazorXTreatmentForSyncGstinBvsValidation('on');

        $this->setBvsValidationDetailForGstinUpdateSelfServe();

        $this->mockStorkForUpdateGstBvsValidationSuccess();

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(3))
            ->method('pushIdentifyAndTrackEvent')
            ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                $this->assertNotNull($properties);
                $this->assertTrue(in_array($eventName, ["Edit gstin bvs result", "Self Serve Success"], true));
            }));

        $response = $this->initiateGstinSelfServe();

        $this->assertEquals(true, $response['sync_flow']);

        $this->assertEquals(false, $response['workflow_created']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  false
        ]);

        $this->processBvsResponseForGstinSelfServe();

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertCacheDataNullForGstinSelfServe($merchant['id']);

        $this->assertArraySelectiveEquals([
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
            'business_registered_pin'     => '400018',
            'business_registered_city'    => 'Mumbai City',
            'business_registered_state'   => 'MH'
        ], $merchantDetail);

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail)
        {
            $this->assertEquals('emails.merchant.gstin_updated_self_serve', $mail->view);

            $this->assertArraySelectiveEquals([
                'gstin'                       => '18AABCU9603R1ZM',
                'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
                'business_registered_pin'     => '400018',
                'business_registered_city'    => 'Mumbai City',
                'business_registered_state'   => 'MH',
                'gstin_operation'             => 'updated'
            ], $mail->viewData);

            return true;
        });

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  false
        ]);
    }

    protected function enableRazorXTreatmentForSyncGstinBvsValidation($value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($value)
                {
                    if ($feature === RazorxTreatment::GSTIN_SYNC)
                    {
                        return $value;
                    }

                    if ($feature === RazorxTreatment::BVS_IN_SYNC)
                    {
                        return $value;
                    }

                    if ($feature === RazorxTreatment::WHATSAPP_NOTIFICATIONS)
                    {
                        return $value;
                    }

                    return 'off';
                }));
    }

    protected function mockStorkForUpdateGstBvsValidationSuccess()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI',
            'business_registered_pin'     => '400018',
            'business_registered_city'    => 'Mumbai City',
            'business_registered_state'   => 'MH'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_gstin_auto_updated', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hey,
Your GSTIN have been updated successfully. The details are provided below.
GSTIN: 18AABCU9603R1ZM
Your registered address is updated as below as per GSTIN certificate
Registered address: 1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI, 400018, Mumbai City, MH
Cheers,
Team Razorpay',
            '1234567890'
        );
    }

    public function testGstinSelfServeBvsValidationSuccessInvalidStateFail()
    {
        extract($this->setupMerchantForGstinSelfServeTest());

        $response = $this->initiateGstinSelfServe();

        $this->assertEquals(false, $response['sync_flow']);

        $this->assertEquals(false, $response['workflow_created']);

        $registeredAddress = '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI, Mumbai City, Nostate, 400018';

        $this->setBvsValidationDetailForGstinUpdateSelfServe($registeredAddress);

        $this->setupWorkflow('edit_gstin_details', 'edit_merchant_gstin_detail');

        $this->assertGstinSelfServeStatusAndRejectionReason([
            'workflow_exists'          =>  false,
            'request_under_validation' =>  true
        ]);

        $this->processBvsResponseForGstinSelfServe();

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertArraySelectiveEquals([
            'gstin'                       =>  null,
            'business_registered_address' => '1302, 13, Test, 18 B G KHER ROAD',
            'business_registered_pin'     => '451111',
            'business_registered_city'    => 'Pune',
            'business_registered_state'   => 'MP'
        ], $merchantDetail);
    }

    protected function processBvsResponseForGstinSelfServe($status = 'success')
    {
        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], $status);

        $this->processBvsResponse($bvsResponse);
    }

    protected function assertGstInCertificateUrlInWorkflowComment($workflowActionId)
    {
        // get workflow action details in Admin Auth
        $this->ba->adminAuth('test');

        $request = [
            'method' => 'GET',
            'url'    => '/w-actions/' . $workflowActionId . '/details',
            'content' => []
        ];

        $this->addPermissionToBaAdmin(PermissionName::VIEW_WORKFLOW_REQUESTS);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['id'], $workflowActionId);

        $expectedComment = 'GstIn Certificate : http://dashboard.razorpay.com/admin/entity/ufh.files/live/file_1cXSLlUU8V9sXl';

        $this->assertEquals($res['comments'][0]['comment'], $expectedComment);
    }

    protected function rejectWorkFlowWithRejectionReason($workflowActionId)
    {
        $rejectionReason = ['subject' => 'Test subject', 'body' => 'Test body'];

        $observerData = [ 'rejection_reason' => $rejectionReason, 'ticket_id' => '123', 'fd_instance' => 'rzpind' ];

        $this->updateObserverData($workflowActionId, $observerData);

        $this->performWorkflowAction($workflowActionId, false);
    }

    protected function assertWorkflowDataForGstInSelfServe($workflowAction, $isAddGstinflow = true)
    {
        $this->assertGstInCertificateUrlInWorkflowComment($workflowAction['id']);

        $this->esClient->indices()->refresh();
        $action = $this->esDao->searchByIndexTypeAndActionId('workflow_action_test_testing', 'action',
            substr($workflowAction['id'], 9))[0]['_source'];

        $permission = ($isAddGstinflow) ? 'edit_merchant_gstin_detail' : 'update_merchant_gstin_detail';

        $this->assertEquals('open', $action['state']);
        $this->assertEquals( 'POST', $action['method']);
        $this->assertEquals('RZP\Http\Controllers\MerchantController@postGstinUpdateWorkflow', $action['controller']);
        $this->assertEquals('merchant_gstin_self_serve_update', $action['route']);
        $this->assertEquals($permission, $action['permission']);

        $this->assertArraySelectiveEquals([
                'gstin'         => '18AABCU9603R1ZM',
            ]
            , $action['payload']);
        $this->assertEquals([], $action['route_params']);

        $this->assertArraySelectiveEquals( [
            'old' => [
                'gstin' => ($isAddGstinflow) ? null : 'abcdefghijklmno'
            ],
            'new' => [
                'gstin' => '18AABCU9603R1ZM'
            ],
        ], $action['diff']);
    }

    protected function assertCacheDataNullForGstinSelfServe($merchantId)
    {
        $cacheData = $this->app['cache']->get('gstin_self_serve_input_' . $merchantId);

        $this->assertNull($cacheData);
    }

    protected function assertCacheDataForGstinSelfServe($merchantId)
    {
        $data = $this->app['cache']->get('gstin_self_serve_input_' . $merchantId);

        $this->assertEquals([
            'gstin'                     => '18AABCU9603R1ZM',
            'gstin_certificate_file_id' => '1cXSLlUU8V9sXl',
            'merchant_id'               => $merchantId,
            'is_add_gstin_operation'    => true,
        ], $data);
    }

    protected function setBvsValidationDetailForGstinUpdateSelfServe($registeredAddress = null)
    {
        if (empty($registeredAddress) === true)
        {
            $registeredAddress = '1302, 13, ORCHID, 18 B G KHER ROAD, WORLI MUMBAI, Mumbai City, Maharashtra, 400018';
        }

        Config::set('services.bvs.validationDetail', [
            'enrichment_details' => get_Protobuf_Struct([
                'online_provider' => [
                    'details' => [
                        'primary_address' => [
                                'value'  => $registeredAddress
                        ],
                    ]
                ]
            ])
        ]);
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

    private function createDocumentEntities(string $merchantId, array $documentTypes, array $attributes = [])
    {
        $data = [
            'document_types' => $documentTypes,
            'attributes'     => [
                'merchant_id'   => $merchantId,
                'file_store_id' => 'abcdefgh12345',]
        ];

        $data['attributes'] = array_merge($data['attributes'], $attributes);

        $this->fixtures->create('merchant_document:multiple', $data);
    }

    private function mockDiag()
    {
        $diagMock = $this->getMockBuilder(DiagClient::class)
                         ->setConstructorArgs([$this->app])
                         ->setMethods(['trackEvent'])
                         ->getMock();

        $this->app->instance('diag', $diagMock);
    }

    private function verifyOnboardingEvent($product = 'primary')
    {
        $this->mockDiag();

        $this->app->diag->method('trackEvent')
                        ->will($this->returnCallback(
                            function (string $eventType,
                                      string $eventVersion,
                                      array $event,
                                      array $properties,
                                      array $metaData = null,
                                      array $readKey = [] ,
                                      string $writeKey = null) use ($product)
                            {
                                if (($event['group'] === 'onboarding') and
                                    ($event['name'] === 'signup.finish_signup.success'))
                                {
                                    $expectedProperties = [
                                        'merchant'  => [],
                                        'source'    => [
                                            'product' => $product
                                        ],
                                    ];

                                    $this->assertArraySelectiveEquals($expectedProperties, $properties);
                                }

                                return;
                            }));
    }

    protected function mockHubSpotClientForProductType($methodName, $param)
    {
        $hubSpotMock = $this->getMockBuilder(HubspotClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods([$methodName])
                            ->getMock();

        $this->app->instance('hubspot', $hubSpotMock);

        $hubSpotMock->expects($this->exactly(1))
                    ->method($methodName)
                    ->will($this->returnCallback(
                        function(array $payloadData) use ($param)
                        {
                            foreach ($param as $key => $value)
                            {
                                    $this->assertArrayHasKey($key, $payloadData);
                                    $this->assertSame($value, $payloadData[$key], 'The key is: '.$key);
                            }
                        }));
    }

    public function testRequestOriginInHubspotPreSignupDetails()
    {
        $this->mockBvsService();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $param  = ["product_type" => "banking"];

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->mockHubSpotClientForProductType('dispatchRequestJob', $param);

        $this->startTest();
    }

    public function testGetNCAdditionalDocuments()
    {

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchant = $merchantDetail->merchant;

        // allow admin to access the merchant
        $admin = $this->ba->getAdmin();

        $admin->merchants()->attach($merchant);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRequestOriginInHubspotPreSignupDetailsForPrimary()
    {
        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = 'https://dashboard.razorpay.com';

        $param  = ["product_type" => "primary"];

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->mockHubSpotClientForProductType('dispatchRequestJob', $param);

        $this->startTest();
    }

    public function testGetBusinessDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testGetBusinessDetailsWithEmptyString()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testSaveMerchantDetailsForActivationValidGSTINcheck()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testSaveMerchantDetailsForActivationNullGstinCheck()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testGetMerchantDetailsShopEstbVerifiableZone()
    {
        $this->fixtures->create('merchant_detail',['merchant_id' => '10000000000000', 'business_type' => '1']);


        $this->verifyShopEstbVerifiableZone([
                                                'business_registered_city'  => 'Bhilwara',
                                                'business_registered_state' => 'RJ'
                                            ],
                                            false);
        $this->verifyShopEstbVerifiableZone([
                                                'business_registered_city'  => 'chandigarh',
                                                'business_registered_state' => 'HA'
                                            ],
                                            true);
        $this->verifyShopEstbVerifiableZone([
                                                'business_registered_city'  => 'Bhopal',
                                                'business_registered_state' => 'MP'
                                            ],
                                            true);

        $this->verifyShopEstbVerifiableZone([
                                                'business_registered_city'  => 'calcutta',
                                                'business_registered_state' => 'XY'
                                            ],
                                            true);
        $this->verifyShopEstbVerifiableZone([
                                                'business_registered_city'  => 'gurgon',
                                                'business_registered_state' => 'XY'
                                            ],
                                            true);
    }


    protected function verifyShopEstbVerifiableZone(array $input, bool $expectedFlag)
    {
        $this->fixtures->on('live')->edit('merchant_detail', '10000000000000', $input);
        $this->fixtures->on('test')->edit('merchant_detail', '10000000000000', $input);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $test = $this->testData['testGetMerchantDetailsShopEstbVerifiableZone'];

        $response = $this->startTest($test);

        $this->assertEquals($expectedFlag, $response['shop_establishment_verifiable_zone']);
    }

    /**
     * @param $status
     *
     * @return mixed
     */
    protected function validatePoaStatusOnL2Submission($status)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      ['aadhar_front'], ['ocr_verify' => $status]);

        $this->createDocumentEntities($merchantDetail[MerchantDetails::MERCHANT_ID],
                                      ['aadhar_back']);

        $testData = &$this->testData['testSubmit'];

        $this->startTest($testData);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($status, $merchantDetail->getPoaVerificationStatus());

        return $testData;
    }

    public function testSaveShopEstbNumberStatusPending()
    {
        $this->verifyVerificationStatusOnSaveDetails(
            ['shop_establishment_number' => 'shopEstbNum123'],
            ['shop_establishment_verification_status' => 'pending'],
            ['merchant_id' => '10000000000000', 'business_type' => '1']
        );
    }

    public function testSaveShopEstbNumberStatusPendingToNull()
    {
        $this->verifyVerificationStatusOnSaveDetails(
            ['shop_establishment_number' => ''],
            ['shop_establishment_verification_status' => null],
            [
                'merchant_id'                            => '10000000000000',
                'business_type'                          => '1',
                'shop_establishment_number'              => 'shopEstbNum123',
                'shop_establishment_verification_status' => 'pending'
            ]
        );
    }

    protected function verifyVerificationStatusOnSaveDetails(array $field,
                                                             array $VerificationStatus,
                                                             array $input = [])
    {
        $this->fixtures->create('merchant_detail', $input);

        $this->ba->proxyAuth();

        $this->mockRazorX('saveMerchantDetailsFields', 'bvs_shop_estb_auth', 'on', '10000000000000');

        $request = $this->testData['saveMerchantDetailsFields']['request'];

        foreach ($field as $fieldKey => $fieldValue)
        {
            $request['content'][$fieldKey] = $fieldValue;
        }

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        foreach ($VerificationStatus as $VerificationStatusKey => $VerificationStatusValue)
        {
            $this->assertEquals($VerificationStatusValue, $content[$VerificationStatusKey] ?? '');
        }

        foreach ($field as $fieldKey => $fieldValue)
        {
            $this->assertEquals($fieldValue, $content[$fieldKey] ?? '');
        }
    }

    protected function assertGstinSelfServeStatusAndRejectionReason($content)
    {
        $data = $this->testData['testGstinSelfServeStatus'];

        $data['response']['content'] = $content;

       $this->startTest($data);
    }

    protected function setupMerchantForGstinSelfServeTest($isAddGstinFlow = true)
    {
        Mail::fake();

        $this->setMockRazorxTreatment(['whatsapp_notifications' => 'on']);

        $merchant = $this->fixtures->merchant->create([
            'activated' => true,
            'name' => 'Test name'
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant['id'],
            'contact_name'      => $merchant['name'],
            'promoter_pan_name' => 'randomLegalName',
            'business_name'     => 'randomTradeName',
            'business_registered_address' => '1302, 13, Test, 18 B G KHER ROAD',
            'business_registered_pin'     => '451111',
            'business_registered_city'    => 'Pune',
            'business_registered_state'   => 'MP',
            'gstin'                       => ($isAddGstinFlow) ? null : 'abcdefghijklmno'
        ]);

        $user = $this->fixtures->create('user', [
            'contact_mobile'          => '1234567890',
            'contact_mobile_verified' => true,
        ]);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        Config::set('services.bvs.mock', true);

        return [
            'merchant'  => $merchant,
            'user'      => $user,
        ];
    }

    private function initiateGstinSelfServe()
    {
        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $response = $this->startTest();

        return $response;
    }

   private function setupMerchantWithMerchantDetails(array $predefinedMerchant = [], array $predefinedMerchantDetails = [], string $role = Role::OWNER)
    {
        $this->setMockRazorxTreatment(['whatsapp_notifications' => 'on']);

        $merchant = $this->fixtures->create('merchant', $predefinedMerchant);

        $merchantId = $merchant['id'];

        $user = $this->fixtures->create('user', [
            'contact_mobile'          => '1234567890',
            'contact_mobile_verified' => true,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => $merchantId,
            'role'        => $role,
        ]);

        $predefinedMerchantDetails = array_merge(['merchant_id'  => $merchantId], $predefinedMerchantDetails );

        $this->fixtures->create('merchant_detail', $predefinedMerchantDetails);

        return [$merchantId, $user['id']];
    }

    private function raiseWorkflowMakerRequestToSaveBusinessWebsiteWithTestCredentials(string $merchantId, string $permissionName, string $userId)
    {
        $this->setupWorkflow("update_website", $permissionName);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId );

        $this->startTest();

        return $merchantId;
    }

    private function raiseWorkflowMakerRequestToSaveBusinessWebsiteWithoutTestCredentials(string $merchantId, string $permissionName, string $userId)
    {
        $this->setupWorkflow("update_website", $permissionName);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId );

        $this->startTest();

        return $merchantId;
    }

    private function setupMerchantUserAndWorkflow(array $predefinedMerchantDetails = [], string $permissionName = PermissionName::UPDATE_MOBILE_NUMBER, string $workflowName = 'update mobile number')
    {
       [$merchantId , $userId] = $this->setupMerchantWithMerchantDetails([], $predefinedMerchantDetails);

        $this->setupWorkflow($workflowName, $permissionName);

        return [$merchantId, $userId];
    }

    private function saveBusinessWebsiteMakerFlow(array $predefinedMerchantDetails = [], string $permissionName = PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL, bool $addTestCredentials = true)
    {
        [$merchantId , $userId] = $this->setupMerchantWithMerchantDetails(['name' => 'Test name', 'has_key_access' => true], $predefinedMerchantDetails);

        if($addTestCredentials === true)
        {
            $this->raiseWorkflowMakerRequestToSaveBusinessWebsiteWithTestCredentials($merchantId, $permissionName, $userId);
        }
        else
        {
            $this->raiseWorkflowMakerRequestToSaveBusinessWebsiteWithoutTestCredentials($merchantId, $permissionName, $userId);
        }

        return $merchantId;
    }

    private function rejectUpdateMerchantContactWorkflowAndAssertData($merchantId , $userId, $workflowActionId)
    {
        $this->performWorkflowAction($workflowActionId, false);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getContactMobile(), '+911234567890');

        $user = $this->getDbEntityById('user', $userId);

        $this->assertEquals($user->getContactMobile() , '1234567890');
    }

    private function updateMerchantContactWorkflowApproveAndAssertData($merchantId , $userId, $workflowActionId)
    {
        $this->performWorkflowAction($workflowActionId, true);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals($merchantDetails->getContactMobile(), '+918722627189');

        $user = $this->getDbEntityById('user', $userId);

        $this->assertEquals($user->getContactMobile() , '8722627189');
    }

    private function validateBusinessWebsiteWorkflowApprove($merchantId , $workflowActionId)
    {
        $this->performWorkflowAction('w_action_'.$workflowActionId, true );

        $merchant = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals('https://www.example.com', $merchant->getWebsite());
    }

    private function validateBusinessWebsiteWorkflowReject($merchantId, $workflowActionId)
    {
        $rejectionReason = ['subject' => 'Test subject', 'body' => 'Test body'];

        $observerData = [ 'rejection_reason' => $rejectionReason, 'ticket_id' => '123', 'fd_instance' => 'rzpind' ];

        $this->updateObserverData('w_action_' . $workflowActionId, $observerData);

        $insertedObserverData = $this->getWorkflowData();

        $this->assertNotEmpty($insertedObserverData);

        $insertedObserverData = $insertedObserverData['workflow_observer_data'];

        $expectedObserverData = ['rejection_reason' => json_encode($rejectionReason), 'ticket_id' => '123', 'fd_instance' => 'rzpind' ];

        $this->assertArraySelectiveEquals($expectedObserverData, $insertedObserverData);

        $this->performWorkflowAction('w_action_'.$workflowActionId, false );

        $merchant = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNotEquals('https://www.example.com', $merchant->getWebsite());
    }

    private function assertWorkflowDataForUpdateMerchantContact($merchantId, $userId, string $permissionName, $merchantContact, $userContact)
    {
        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $user = $this->getDbEntityById('user', $userId);

        $this->assertEquals($merchantDetails->getContactMobile(), $merchantContact);

        $this->assertEquals($user->getContactMobile(), $userContact);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $this->esClient->indices()->refresh();

        $action = $this->esDao->searchByIndexTypeAndActionId('workflow_action_test_testing', 'action',
                                                             substr($workflowAction['id'], 9))[0]['_source'];

        $this->assertEquals('open', $action['state']);
        $this->assertEquals('PUT', $action['method']);
        $this->assertEquals('RZP\Http\Controllers\MerchantController@putMerchantContactUpdatePostWorkflow', $action['controller']);
        $this->assertEquals('update_merchant_mobile_number', $action['route']);
        $this->assertEquals($permissionName, $action['permission']);

        $this->assertArraySelectiveEquals([
                                              'old_contact_number' => "1234567890",
                                              'new_contact_number' => "8722627189",
                                          ], $action['payload']);
        $this->assertEquals(['id' => $merchantId], $action['route_params']);

        if ($merchantContact == '8722627189')
        {
            $this->assertArraySelectiveEquals([
                                                  'old' => [
                                                      'user_contact_mobile' => '1234567890',
                                                  ],
                                                  'new' => [
                                                      'user_contact_mobile' => '8722627189',
                                                  ],
                                              ], $action['diff']);
        }
        else
        {
            $this->assertArraySelectiveEquals([
                                                  'old' => [
                                                      'contact_mobile'      => $merchantContact,
                                                      'user_contact_mobile' => '1234567890',
                                                  ],
                                                  'new' => [
                                                      'contact_mobile'      => '8722627189',
                                                      'user_contact_mobile' => '8722627189',
                                                  ],
                                              ], $action['diff']);
        }

        return [$merchantId, $workflowAction['id']];
    }

    private function validateBusinessWebsiteWorkflow($merchantId, string $permissionName = PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL)
    {
        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertNotEquals($merchant->getWebsite(), 'https://www.example.com');

        $permission = (new PermissionRepository)->findByOrgIdAndPermission(
            Org::RZP_ORG, $permissionName
        );

        $workflowActions = (new ActionRepository)->getOpenActionOnEntityOperation(
            $merchantId, 'merchant_detail', $permission->getId()
        );

        $this->assertNotEmpty($workflowActions);

        $workflowAction  =  $workflowActions[0];

        $workflowAction = $workflowAction->toArray();

        $this->esClient->indices()->refresh();

        return [$merchantId, $workflowAction['id']];
    }

    public function testUpdateMerchantContactWithWorkflowReject()
    {
        [$merchantId, $userId] = $this->setupMerchantUserAndWorkflow(['contact_mobile' => "1234567890"]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->ba->adminAuth();

        $this->startTest();

        [$merchantId, $workflowActionId] = $this->assertWorkflowDataForUpdateMerchantContact($merchantId, $userId,
                                                                                             PermissionName::UPDATE_MOBILE_NUMBER,
                                                                                             '+911234567890', '1234567890');

        $this->rejectUpdateMerchantContactWorkflowAndAssertData($merchantId, $userId, $workflowActionId);
    }

    public function testUpdateMerchantContactNoOwnerExistWithContactNumberFail()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant['id'];

        $user = $this->fixtures->create('user', [
            'contact_mobile'          => '0123456789',
            'contact_mobile_verified' => true,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user->id,
                                                             'merchant_id' => $merchantId,
                                                             'role'        => 'owner',
                                                         ]);

        $predefinedMerchantDetails = [
            'merchant_id'    => $merchantId,
            'contact_mobile' => '1234567890'
        ];

        $this->fixtures->create('merchant_detail', $predefinedMerchantDetails);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->ba->adminAuth();

        $this->startTest();
    }

    /**
     * Validation failure for a contact number which already exists for some other merchant or users
     */
    public function testUpdateMerchantContactWithContactAlreadyExistsFailure()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant['id'];

        $user = $this->fixtures->create('user', [
            'contact_mobile'          => '8722627189',
            'contact_mobile_verified' => true,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user->id,
                                                             'merchant_id' => $merchantId,
                                                             'role'        => 'owner',
                                                         ]);

        $predefinedMerchantDetails = ['merchant_id'  => $merchantId, 'contact_mobile' => '8722627189'];

        [$merchantId2, $userId2] = $this->setupMerchantWithMerchantDetails([], $predefinedMerchantDetails);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId2/mobile";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantContactMultipleOwnersExistWithContactNumberFail()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant['id'];

        $user1 = $this->fixtures->create('user', [
            'contact_mobile'          => '1234567890',
            'contact_mobile_verified' => true,
        ]);

        $user2 = $this->fixtures->create('user', [
            'contact_mobile'          => '1234567890',
            'contact_mobile_verified' => true,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user1->id,
                                                             'merchant_id' => $merchantId,
                                                             'role'        => 'owner',
                                                         ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                              'user_id'     => $user2->id,
                                                              'merchant_id' => $merchantId,
                                                              'role'        => 'owner',
                                                          ]);

        $predefinedMerchantDetails = array_merge(['merchant_id'  => $merchantId], ['contact_mobile' => '1234567890'] );

        $this->fixtures->create('merchant_detail', $predefinedMerchantDetails);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantContactWithSameNewNumberAndMerchantContactDetails()
    {
        Mail::fake();

        [$merchantId, $userId] = $this->setupMerchantUserAndWorkflow(['contact_mobile' => "8722627189"]);

        $userDb1 = $this->getDbEntityById('user',  $userId);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            if ($primaryMids[$i] != $merchantId)
            {
                $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                    'merchant_id' => $primaryMids[$i],
                    'contact_mobile' => '123456789' . $i,
                    'contact_email' => 'user'. $i. '@email.com',
                ]);
            }
        }

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->ba->adminAuth();

        $this->startTest();

        [$merchantId, $workflowActionId] = $this->assertWorkflowDataForUpdateMerchantContact($merchantId, $userId,
                                                                                             PermissionName::UPDATE_MOBILE_NUMBER,
                                                                                             '+918722627189', '1234567890');

        $this->updateMerchantContactWorkflowApproveAndAssertData($merchantId, $userId, $workflowActionId);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+918722627189");
        }

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($merchant)
        {
            $data = $mail->viewData;

            $this->assertEquals('1234567890', $data['old_contact_number']);

            $this->assertEquals('8722627189', $data['new_contact_number']);

            $this->assertEquals('emails.merchant.update_merchant_contact_from_admin', $mail->view);

            $mail->hasTo($merchant['email']);

            return true;
        });
    }

    public function testUpdateMerchantContactWithWorkflow()
    {
        Mail::fake();

        [$merchantId, $userId] = $this->setupMerchantUserAndWorkflow(['contact_mobile' => "1234567890"]);

        $userDb1 = $this->getDbEntityById('user',  $userId);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            if ($primaryMids[$i] != $merchantId)
            {
                $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                    'merchant_id' => $primaryMids[$i],
                    'contact_mobile' => '123456789' . $i,
                    'contact_email' => 'user'. $i. '@email.com',
                ]);
            }
        }

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->ba->adminAuth();

        $this->startTest();

        [$merchantId, $workflowActionId] = $this->assertWorkflowDataForUpdateMerchantContact($merchantId, $userId,
                                                                                             PermissionName::UPDATE_MOBILE_NUMBER,
                                                                                             "+911234567890", "1234567890");

        $this->updateMerchantContactWorkflowApproveAndAssertData($merchantId, $userId, $workflowActionId);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+918722627189");
        }

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($merchant)
        {
            $data = $mail->viewData;

            $this->assertEquals('1234567890', $data['old_contact_number']);

            $this->assertEquals('8722627189', $data['new_contact_number']);

            $this->assertEquals('emails.merchant.update_merchant_contact_from_admin', $mail->view);

            $mail->hasTo($merchant['email']);

            return true;
        });
    }

    public function testUpdateMerchantContactWithOwnerHavingRoleForPrimaryAndBanking()
    {
        Mail::fake();

        [$merchantId, $userId] = $this->setupMerchantUserAndWorkflow(['contact_mobile' => "1234567890"]);

        $userDb1 = $this->getDbEntityById('user',  $userId);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            if ($primaryMids[$i] != $merchantId)
            {
                $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                    'merchant_id' => $primaryMids[$i],
                    'contact_mobile' => '123456789' . $i,
                    'contact_email' => 'user'. $i. '@email.com',
                ]);
            }
        }

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $userId,
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'banking'
        ]);

        $testData = &$this->testData['testUpdateMerchantContactWithWorkflow'];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->adminAuth();

        $this->startTest();

        [$merchantId, $workflowActionId] = $this->assertWorkflowDataForUpdateMerchantContact($merchantId, $userId,
            PermissionName::UPDATE_MOBILE_NUMBER,
            "+911234567890", "1234567890");

        $this->updateMerchantContactWorkflowApproveAndAssertData($merchantId, $userId, $workflowActionId);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+918722627189");
        }

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($merchant)
        {
            $data = $mail->viewData;

            $this->assertEquals('1234567890', $data['old_contact_number']);

            $this->assertEquals('8722627189', $data['new_contact_number']);

            $this->assertEquals('emails.merchant.update_merchant_contact_from_admin', $mail->view);

            $mail->hasTo($merchant['email']);

            return true;
        });
    }

    public function testUpdateBusinessWebsiteWorkflowApprove()
    {
        Mail::fake();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'updated_business_website'  => 'https://www.example.com',
            'previous_business_website' => 'https://www.sample.com'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_business_website_update', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'As per your request, we have changed your website from https://www.sample.com to https://www.example.com
You can now start accepting payments from https://www.example.com.
-Team Razorpay',
            '1234567890'
        );

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $this->validateBusinessWebsiteWorkflowApprove($merchantId, $workflowActionId);

        $user = $this->getDbLastEntity('user');

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('https://www.example.com', $data['updated_business_website']);

            $this->assertEquals('https://www.sample.com', $data['previous_business_website']);

            $this->assertEquals('emails.merchant.merchant_business_website_update', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });
    }

    public function testBusinessWebsiteAdditionWorkflowApprove()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['activation_status' => 'activated']);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId);

        $this->mockStorkForBusinessWebsiteAdd();

        $this->validateBusinessWebsiteWorkflowApprove($merchantId, $workflowActionId);

        $user = $this->getDbLastEntity('user');

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('https://www.example.com', $data['updated_business_website']);

            $this->assertEquals('emails.merchant.merchant_business_website_add', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });
    }

    public function testWebsiteAddWorkflowNeedsClarification()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['activation_status' => 'activated']);

        $expectedStorkParametersForSMSTemplate = [
            'merchant_name'  => 'Test name',
        ];

        $this->raiseNeedWorkflowClarificationFromMerchantAndAssert([
            'expected_whatsapp_text'    => 'Hi Test name, we need a few more details to process the request on adding website/app to your Razorpay account. Please click https://dashboard.razorpay.com/app/profile/clarification_add_website to share the details. -Team Razorpay',
            'expected_index_of_comment' => 2,
            'expected_sms_template'     => 'sms.dashboard.merchant_website_add_needs_clarification',
            'expected_deep_link'        => 'https://dashboard.razorpay.com/app/profile/clarification_add_website'
        ], $expectedStorkParametersForSMSTemplate);

        return $merchantId;
    }

    public function testWebsiteUpdateWorkflowNeedsClarification()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        $expectedStorkParametersForSMSTemplate = [
            'merchant_name'  => 'Test name',
        ];

        $this->raiseNeedWorkflowClarificationFromMerchantAndAssert([
            'expected_whatsapp_text'    => 'Hi Test name, we need a few more details to process the request on updating your Razorpay website/app. Please click https://dashboard.razorpay.com/app/profile/clarification_update_website to share the details. -Team Razorpay',
            'expected_index_of_comment' => 2,
            'expected_sms_template'     => 'sms.dashboard.merchant_website_update_needs_clarification',
            'expected_deep_link'        => 'https://dashboard.razorpay.com/app/profile/clarification_update_website'
        ], $expectedStorkParametersForSMSTemplate);

        return $merchantId;
    }

    public function testWebsiteUpdateGetWorkflowNeedsClarificationQuery()
    {
        $merchantId = $this->testWebsiteUpdateWorkflowNeedsClarification();

        $this->getNeedsClarificationQueryAndAssert($merchantId, 'update_business_website');
    }

    protected function raiseNeedWorkflowClarificationFromMerchantAndAssert($data, $expectedStorkParametersForSMSTemplate)
    {
        $this->setMockRazorxTreatment(['whatsapp_notifications' => 'on']);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock,$data['expected_sms_template'], '1234567890', $expectedStorkParametersForSMSTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            $data['expected_whatsapp_text'],
            '1234567890'
        );

        $this->esClient->indices()->refresh();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $testData = $this->testData['testNeedClarificationOnWorkflow'];

        $testData['request']['url'] = '/merchant/' . $workflowAction['id'] . '/need_clarification';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $request = [
            'method' => 'GET',
            'url'    => '/w-actions/' . $workflowAction['id'] . '/details',
            'content' => []
        ];

        $this->addPermissionToBaAdmin(PermissionName::VIEW_WORKFLOW_REQUESTS);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['id'], $workflowAction['id']);

        $expectedComment = 'need_clarification_comment : needs clarification body';

        $this->assertEquals($expectedComment, $res['comments'][$data['expected_index_of_comment']]['comment']);

        $this->assertEquals('awaiting-customer-response', $res['tagged'][0]);

        $this->assertWorkflowNeedsClarificationMailQueued($data['expected_deep_link']);
    }

    public function testUpdateBusinessWebsiteWorkflowReject()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $this->validateBusinessWebsiteWorkflowReject($merchantId, $workflowActionId);

        Mail::assertNotQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            if ($mail->view === 'emails.merchant.merchant_business_website_update')
            {
                return true;
            }
            return false;
        });
    }

    public function testBusinessWebsiteOpenWorkflowStatus()
    {
        $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        $this->startTest();
    }

    public function testGetMerchantWorkflowDetailsByInternalAuth()
    {
        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        $testData = $this->testData['testGetMerchantWorkflowDetailsByInternalAuth'];

        $testData['request']['url'] = '/internal/merchant/' . $merchantId . '/workflow_details';

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->terminalsAuth();

        $this->startTest();
    }


    public function testUpdateBusinessWebsiteAppRoleFail()
    {
        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails(['has_key_access' => true], ['activation_status' => 'activated'], Role::SELLERAPP);

        $this->setupWorkflow("update_website", PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId );

        $this->startTest();

        return $merchantId;
    }

    public function testBusinessWebsiteEncryption()
    {
        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/w_action_'.$workflowActionId.'/decrypt_website_comment';

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBusinessWebsiteEncryptionCommentNotFound()
    {
        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE , false);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/w_action_' . $workflowActionId . '/decrypt_website_comment';

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRejectionReasonMerchantNotificationForWebsiteUpdateSelfServe()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $this->mockStorkForUpdateWebsiteRejectionReason();

        $this->validateBusinessWebsiteWorkflowReject($merchantId, $workflowActionId);

        $user = $this->getDbLastEntity('user');

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('Test body', $data['messageBody']);

            $this->assertEquals('emails.merchant.rejection_reason_notification', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    protected function assertWorkflowNeedsClarificationMailQueued($deepLink, $messageBody = 'needs clarification body')
    {
        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use ($deepLink, $messageBody)
        {
            if ($mail->view === 'emails.merchant.needs_clarification_on_workflow')
            {
                $this->assertEquals($deepLink, $mail->viewData['workflow_clarification_submit_link']);

                $this->assertEquals($messageBody, $mail->viewData['messageBody']);

                return true;
            }

            return false;
        });
    }

    protected function mockStorkForUpdateWebsiteRejectionReason()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'merchant_name' => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_business_website_update_rejection', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hi Test name, Your request for updating the website/app has been rejected. Please click on https://dashboard.razorpay.com/app/profile/rejection_update_website to know more.
-Team Razorpay',
            '1234567890'
        );
    }

    protected function expectStorkOptInStatusForWhatsapp($storkMock, $destination): void
    {
        $storkMock->shouldReceive('optInStatusForWhatsapp')
                  ->times(1)
                  ->with(
                      Mockery::on(function ($mode)
                      {
                          return true;
                      }),
                      Mockery::on(function ($actualReceiver) use($destination)
                      {
                          if ($actualReceiver !== $destination)
                          {
                              return false;
                          }
                          return true;
                      }),
                      Mockery::on(function ($input)
                      {
                          return true;
                      }))
                  ->andReturnUsing(function ()
                  {
                      return ['consent_status' => true];
                  });
    }

    protected function expectStorkWhatsappRequest($storkMock, $text, $destination): void
    {
        $storkMock->shouldReceive('sendWhatsappMessage')
            ->times(1)
            ->with(
                Mockery::on(function ($mode)
                {
                    return true;
                }),
                Mockery::on(function ($actualText) use($text)
                {
                    $actualText = trim(preg_replace('/\s+/', ' ', $actualText));

                    $text = trim(preg_replace('/\s+/', ' ', $text));

                    if ($actualText !== $text)
                    {
                        return false;
                    }

                    return true;
                }),
                Mockery::on(function ($actualReceiver) use($destination)
                {
                    if ($actualReceiver !== $destination)
                    {
                        return false;
                    }
                    return true;
                }),
                Mockery::on(function ($input)
                {
                    return true;
                }))
            ->andReturnUsing(function ()
            {
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

    protected function expectStorkSendSmsRequest($storkMock, $templateName, $destination, $expectedParms = [])
    {
        $storkMock->shouldReceive('sendSms')
                  ->times(1)
                  ->with(
                      Mockery::on(function ($mockInMode)
                      {
                          return true;
                      }),
                      Mockery::on(function ($actualPayload) use ($templateName, $destination, $expectedParms)
                      {

                          // We are sending null in contentParams in the payload if there is no SMS_TEMPLATE_KEYS present for that event
                          // Reference: app/Notifications/Dashboard/SmsNotificationService.php L:99
                          if(isset($actualPayload['contentParams']) === true)
                          {
                              $this->assertArraySelectiveEquals($expectedParms, $actualPayload['contentParams']);
                          }

                          if (($templateName !== $actualPayload['templateName']) or
                               ($destination !== $actualPayload['destination']))
                          {
                              return false;
                          }

                          return true;
                      }))
                  ->andReturnUsing(function ()
                  {
                      return ['success' => true];
                  });
    }

    protected function mockStorkForAddAdditionalWebsiteSelfServe()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'additional_website' => 'https://www.example.com',
            'merchant_name'      => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_additional_website_successful', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
                                          'Hi Test name,
We have successfully added the https://www.example.com to your Razorpay account. You can now start accepting payments from it.
We look forward to transacting with you!
-Team Razorpay',
                                          '1234567890'
        );
    }

    public function testAddAdditionalWebsiteSelfServeWorkflowApprove()
    {
        Mail::fake();

        $predefinedMerchant = [
            'name'                  => 'Test name',
            'has_key_access'        => true,
            'whitelisted_domains'   => ['sample.com', 'abc.com']
        ];

        $predefinedMerchantDetails = [
            'business_website'      => 'https://www.businesssample.com',
            'additional_websites'   => ['https://www.sample.com', 'https://www.abc.com'],
            'activation_status'     => 'activated'
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this-> mockStorkForAddAdditionalWebsiteSelfServe();

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();

        (new PermissionRepository)->findByOrgIdAndPermission(
            Org::RZP_ORG, PermissionName::ADD_ADDITIONAL_WEBSITE
        );

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $this->esClient->indices()->refresh();

        $this->performWorkflowAction($workflowAction['id'], true );

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertContains('https://www.example.com' , $merchantDetails->getAdditionalWebsites());

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertContains('example.com', $merchant->getWhitelistedDomains());

        $user = $this->getDbLastEntity('user');

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('https://www.example.com', $data['additional_website']);

            $this->assertEquals('emails.merchant.add_additional_website_success', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });
    }

    protected function mockStorkForAdditionalWebsiteSelfServeWorkflowNeedClarification()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'merchant_name'  => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_additional_website_clarification', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
                                          'Hi Test name, we need a few more details to process the request on adding website/app to your Razorpay account.
            -Team Razorpay',
                                          '1234567890'
        );
    }

    public function testAddAdditionalWebsiteSelfServeWorkflowNeedsClarification()
    {
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testAddAdditionalWebsiteSelfServeWorkflowApprove'];

        $predefinedMerchant = [
            'name'                  => 'Test name',
            'has_key_access'        => true,
            'whitelisted_domains'   => ['sample.com', 'abc.com']
        ];

        $predefinedMerchantDetails = [
            'business_website'      => 'https://www.businesssample.com',
            'additional_websites'   => ['https://www.sample.com', 'https://www.abc.com'],
            'activation_status'     => 'activated'
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->mockStorkForAdditionalWebsiteSelfServeWorkflowNeedClarification();

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();

        (new PermissionRepository)->findByOrgIdAndPermission(
            Org::RZP_ORG, PermissionName::ADD_ADDITIONAL_WEBSITE
        );

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $this->esClient->indices()->refresh();

        $testData = $this->testData['testNeedClarificationOnWorkflow'];

        $testData['request']['url'] = '/merchant/' . $workflowAction['id'] . '/need_clarification';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $request = [
            'method' => 'GET',
            'url'    => '/w-actions/' . $workflowAction['id'] . '/details',
            'content' => []
        ];

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['id'], $workflowAction['id']);

        $this->assertEquals('awaiting-customer-response', $res['tagged'][0]);

        $this->assertWorkflowNeedsClarificationMailQueued('https://dashboard.razorpay.com/app/profile/clarification_additional_website');

    }

    public function testAddAdditionalWebsiteSelfServeMerchantActivationFailure()
    {
        $predefinedMerchantDetails = [
            'activation_status'     => 'under_review'
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails([], $predefinedMerchantDetails);

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();
    }

    public function testAddAdditionalWebsiteSelfServeRoleFailure()
    {
        $predefinedMerchantDetails = [
            'activation_status'     => 'activated'
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails([], $predefinedMerchantDetails, Role::MANAGER);

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();
    }

    public function testAddAdditionalWebsiteSelfServeBusinessWebsiteFailure()
    {
        $predefinedMerchantDetails = [
            'activation_status'     => 'activated',
            'business_website'      => null
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails([], $predefinedMerchantDetails);

        $testData = $this->testData['testAddAdditionalWebsiteSelfServeMerchantActivationFailure'];

        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET;

        $testData['exception']['internal_error_code'] = ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET;

        $this->testData[__FUNCTION__] = $testData;

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();
    }

    public function testAddAdditionalWebsiteSelfServeAccessKeyFailure()
    {
        $predefinedMerchant = [
            'has_key_access'        => false,
        ];

        $predefinedMerchantDetails = [
            'business_website'      => 'https://www.businesssample.com',
            'activation_status'     => 'activated'
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $testData = $this->testData['testAddAdditionalWebsiteSelfServeMerchantActivationFailure'];

        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_MERCHANT_NO_KEY_ACCESS;

        $testData['exception']['internal_error_code'] = ErrorCode::BAD_REQUEST_MERCHANT_NO_KEY_ACCESS;

        $this->testData[__FUNCTION__] = $testData;

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();
    }

    public function testAddAdditionalWebsiteSelfServeMaxLimitFailure()
    {
        $predefinedMerchant = [
            'has_key_access'        => true,
        ];

        $websites = $this->getCollectionOfWebsites(DetailConstants::ADDITIONAL_DOMAIN_WHITELISTING_LIMIT);

        $predefinedMerchantDetails = [
            'business_website'      => 'https://www.businesssample.com',
            'additional_websites'   => $websites,
            'activation_status'     => 'activated'
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $testData = $this->testData['testAddAdditionalWebsiteSelfServeMerchantActivationFailure'];

        $testData['response']['content']['error']['description'] = 'Additional websites may not have more than 5 items';

        $testData['exception']['class'] = 'RZP\Exception\BadRequestValidationFailureException';

        $testData['exception']['internal_error_code'] = ErrorCode::BAD_REQUEST_VALIDATION_FAILURE;

        $this->testData[__FUNCTION__] = $testData;

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();
    }

    protected function mockStorkForAddAdditionalWebsiteRejectionReason()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_additional_website_rejection', '1234567890');

        $this->expectStorkWhatsappRequest($storkMock,
            'Hi Test name, Your request for adding your website/app to Razorpay account has been rejected.
            -Team Razorpay',
            '1234567890'
        );
    }

    public function testAddAdditionalWebsiteSelfServeWorkflowReject()
    {
        Mail::fake();

        $predefinedMerchant = [
            'name'                  => 'Test name',
            'has_key_access'        => true,
            'whitelisted_domains'   => ['sample.com', 'abc.com']
        ];

        $predefinedMerchantDetails = [
            'business_website'      => 'https://www.businesssample.com',
            'additional_websites'   => ['https://www.sample.com', 'https://www.abc.com'],
            'activation_status'     => 'activated'
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->mockStorkForAddAdditionalWebsiteRejectionReason();

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $testData = $this->testData['testAddAdditionalWebsiteSelfServeWorkflowApprove'];

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();

        (new PermissionRepository)->findByOrgIdAndPermission(
            Org::RZP_ORG, PermissionName::ADD_ADDITIONAL_WEBSITE
        );

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $this->esClient->indices()->refresh();

        $this->rejectWorkFlowWithRejectionReason($workflowAction['id']);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNotContains('https://www.example.com' , $merchantDetails->getAdditionalWebsites());

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertNotContains('example.com', $merchant->getWhitelistedDomains());

        Mail::assertNotQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {

            if ($mail->view === 'emails.merchant.add_additional_website_success')
            {
                return true;
            }

            return false;
        });

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) {

            if ($mail->view === 'emails.merchant.rejection_reason_notification')
            {
                $data = $mail->viewData;

                $this->assertEquals('Test body', $data['messageBody']);

                return true;
            }
        });
    }

    protected function mockStorkForAdditionalApp()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'additional_website' => 'https://play.google.com/store/apps/details?id=com.abc.app.test',
            'merchant_name'      => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_additional_website_successful', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
                                          'Hi Test name,
We have successfully added the https://play.google.com/store/apps/details?id=com.abc.app.test to your Razorpay account. You can now start accepting payments from it.
We look forward to transacting with you!
-Team Razorpay',
                                          '1234567890'
        );
    }

    public function testAddAdditionalApp()
    {
        Mail::fake();

        $predefinedMerchant = [
            'name'                  => 'Test name',
            'has_key_access'        => true,
        ];

        $predefinedMerchantDetails = [
            'activation_status'     => 'activated',
            'business_website'      => 'https://www.businesssample.com',
            'additional_websites'   => [
                'https://www.sample.com',
                'https://www.abc.com'
            ]
        ];

        [$merchantId, $userId] = $this->setupMerchantWithMerchantDetails($predefinedMerchant, $predefinedMerchantDetails);

        $this->setupWorkflow('website_domain_whitelist', PermissionName::ADD_ADDITIONAL_WEBSITE);

        $this-> mockStorkForAdditionalApp();

        $this->ba->proxyAuth('rzp_test_'.$merchantId, $userId);

        $this->startTest();

        (new PermissionRepository)->findByOrgIdAndPermission(
            Org::RZP_ORG, PermissionName::ADD_ADDITIONAL_WEBSITE
        );

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $this->esClient->indices()->refresh();

        $this->performWorkflowAction($workflowAction['id'], true );

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertContains('https://play.google.com/store/apps/details?id=com.abc.app.test', $merchantDetails->getAdditionalWebsites());

        $user = $this->getDbLastEntity('user');

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('https://play.google.com/store/apps/details?id=com.abc.app.test', $data['additional_website']);

            $this->assertEquals('emails.merchant.add_additional_website_success', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });
    }

    public function testSubmitMerchantWorkflowClarification()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $workflowAction = $this->getDbLastEntity('workflow_action');

        $this->assertNotEmpty($workflowAction);

        $workflowAction->tag('awaiting-customer-response');

        $workflowAction->save();

        $testData                   =&  $this->testData[__FUNCTION__];
        $testData['request']['url'] =   '/merchant/submit_clarification/update_business_website';

        $this->startTest();

        $this->esClient->indices()->refresh();

        // get workflow action details in Admin Auth
        $this->ba->adminAuth();

        $request = [
            'method' => 'GET',
            'url'    => '/w-actions/w_action_' . $workflowAction->getId() . '/details',
            'content' => []
        ];

        $workflowAction = $this->getLastEntity('workflow_action' , true);

        $this->assertNotEmpty($workflowAction);

        $this->addPermissionToBaAdmin(PermissionName::VIEW_WORKFLOW_REQUESTS);

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['id'], $workflowAction['id']);

        $expectedComment1 = ' Merchant test workflow clarification ';

        $this->assertEquals($res['comments'][2]['comment'], $expectedComment1);

        $expectedComment2 =  'Files shared by merchant: ' . $this->app->config->get('applications.dashboard.url') . 'admin/entity/ufh.files/live/file_randomId1 ,  ';

        $this->assertEquals($res['comments'][3]['comment'], $expectedComment2);

        $expectedTags = ['customer-responded'];

        $this->assertArrayHasKey('tagged', $res );

        $this->assertArraySelectiveEquals($expectedTags , $res['tagged']);
    }

    public function assertBankingEntitiesNotNullInTestMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'test');


        $this->assertNotNull($bankAccount);

        $entityId = $bankAccount['entity_id'];

        $bankAccountId = $bankAccount['id'];

        $virtualAccount = $this->getDbEntity('virtual_account',
            [
                'merchant_id'     => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'id'              => $entityId,
                'bank_account_id' => $bankAccountId
            ], 'test');

        $this->assertNotNull($virtualAccount);

        $balanceId = $virtualAccount['balance_id'];

        $balance = $this->getDbEntity('balance',
            [
                'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'         => 'banking',
                'account_type' => 'shared',
                'id'           => $balanceId
            ], 'test');

        $this->assertNotNull($balance);

        $accountNumber = $balance['account_number'];

        $bankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id'    => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'account_type'   => 'nodal',
                'account_number' => $accountNumber,
                'balance_id'     => $balanceId
            ], 'test');

        $this->assertNotNull($bankingAccount);
    }

    public function assertBankingEntitiesNotNullInLiveMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'live');


        $this->assertNotNull($bankAccount);

        $entityId = $bankAccount['entity_id'];

        $bankAccountId = $bankAccount['id'];

        $virtualAccount = $this->getDbEntity('virtual_account',
            [
                'merchant_id'     => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'id'              => $entityId,
                'bank_account_id' => $bankAccountId
            ], 'live');

        $this->assertNotNull($virtualAccount);

        $balanceId = $virtualAccount['balance_id'];

        $balance = $this->getDbEntity('balance',
            [
                'merchant_id'  => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'         => 'banking',
                'account_type' => 'shared',
                'id'           => $balanceId
            ], 'live');

        $this->assertNotNull($balance);

        $accountNumber = $balance['account_number'];

        $bankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id'    => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'account_type'   => 'nodal',
                'account_number' => $accountNumber,
                'balance_id'     => $balanceId
            ], 'live');

        $this->assertNotNull($bankingAccount);
    }

    public function assertBankingEntitiesNullInTestMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'test');


        $this->assertNull($bankAccount);
    }

    public function assertBankingEntitiesNullInLiveMode($merchantDetail)
    {
        $bankAccount = $this->getDbEntity('bank_account',
            [
                'merchant_id' => $merchantDetail[MerchantDetails::MERCHANT_ID],
                'type'        => 'virtual_account'
            ], 'live');


        $this->assertNull($bankAccount);
    }

    public function fillPgKyc(bool $businessBanking = false, string $businessType = '1')
    {
        Mail::fake();

        $merchantId = self::DEFAULT_MERCHANT_ID;

        $website = 'http://abc.com';

        $this->fixtures->edit('merchant', $merchantId, ['website' => $website, 'whitelisted_domains' => ['abc.com'], 'business_banking' => $businessBanking]);

        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'business_website' => $website, 'issue_fields' => 'business_website', "submitted" => true, Entity::BUSINESS_TYPE => $businessType]);

        $pricingPlanId = $this->fixtures->create('pricing', [
            'product'        => 'banking',
            'id'             => '1zE31zbybacac1',
            'plan_id'        => '1hDYlICobzOCYt',
            'plan_name'      => 'testDefaultPlan',
            'feature'        => 'fund_account_validation',
            'payment_method' => 'bank_account',
            'percent_rate'   => 900,
            'org_id'         => '100000razorpay',
            'type'           => 'pricing',
        ]);

        $this->fixtures->edit('merchant',$merchantId, [
            'name'             => ' Kill Bill Pandey ',
            'billing_label'    => ' AB ',
            'pricing_plan_id'  => $pricingPlanId['plan_id'],
            'activated'        => false,
            'international'    => 0,
            'category2'        => null
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id' => '10000000000000',
        ]);

        $testData = & $this->testData['testPgKycActivation'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->fixtures->on('live')->edit('terminal','BANKACC3DSN3DT',
            ['gateway_merchant_id' => '456456']);

        $this->fixtures->on('live')->edit('terminal','BANKACC3DSN3DZ',
            ['gateway_merchant_id' => '232323']);

        $this->fixtures->on('test')->edit('terminal','BANKACC3DSN3DT',
            ['gateway_merchant_id' => '456456']);

        $this->fixtures->on('test')->edit('terminal','BANKACC3DSN3DZ',
            ['gateway_merchant_id' => '232323']);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->startTest($testData);

        return $merchantDetail;
    }

    public function testVaActivationOnPgKycForRegisteredBusinessXUser()
    {
        $merchantDetail = $this->fillPgKyc(true, '1');

        Mail::assertQueued(AccountActivationConfirmation::class, function ($mail)
        {
            $this->assertEquals('emails.merchant.razorpayx.account_activation_confirmation', $mail->view);

            return true;
        });

        $this->assertBankingEntitiesNotNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNotNullInLiveMode($merchantDetail);
    }

    public function testVaActivationOnPgKycForUnregisteredBusinessXUser()
    {
        $merchantDetail = $this->fillPgKyc(true, '11');

        Mail::assertNotQueued(AccountActivationConfirmation::class);

        $this->assertBankingEntitiesNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNullInLiveMode($merchantDetail);
    }

    public function testVaActivationOnPgKycForRegisteredBusinessPgUser()
    {
        $merchantDetail = $this->fillPgKyc(false, '1');

        Mail::assertNotQueued(AccountActivationConfirmation::class);

        $this->assertBankingEntitiesNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNullInLiveMode($merchantDetail);
    }

    public function testVaActivationOnPgKycForUnRegisteredBusinessPgUser()
    {
        $merchantDetail = $this->fillPgKyc(false, '11');

        Mail::assertNotQueued(AccountActivationConfirmation::class);

        $this->assertBankingEntitiesNullInTestMode($merchantDetail);

        $this->assertBankingEntitiesNullInLiveMode($merchantDetail);
    }

    protected function getNeedsClarificationQueryAndAssert($merchantId, $workflowType)
    {
        $user = $this->fixtures->user->create();

        $this->fixtures->user->createUserMerchantMapping([
            'user_id' => $user['id'],
            'merchant_id' => $merchantId,
            'role' => Role::OWNER,
        ]);

        $testData = $this->testData['testMerchantWorkflowDetailForMerchantWorkflowType'];

        $testData['response']['content'] = [
            'workflow_exists' => true,
            'workflow_status' => 'open',
            'needs_clarification' => 'needs clarification body',
        ];

        $testData['request']['url'] = "/merchant/" . $workflowType . "/details";

        $this->testData[__FUNCTION__] = $testData;

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

    }

    public function testRejectionReasonMerchantNotificationForWebsiteAddSelfServe()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['activation_status' => 'activated'], PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL);

        $this->mockStorkForAddWebsiteRejectionReason();

        $this->validateBusinessWebsiteWorkflowReject($merchantId, $workflowActionId);

        $user = $this->getDbLastEntity('user');

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail) use($user)
        {
            $data = $mail->viewData;

            $this->assertEquals('Test body', $data['messageBody']);

            $this->assertEquals('emails.merchant.rejection_reason_notification', $mail->view);

            $mail->hasTo($user['email']);

            return true;
        });

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    protected function mockStorkForAddWebsiteRejectionReason()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'merchant_name' => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_business_website_add_rejection', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
                                          'Hi Test name, Your request for adding your website/app to your Razorpay account has been rejected. Please click on https://dashboard.razorpay.com/app/profile/rejection_add_website to know more.
-Team Razorpay',
                                          '1234567890'
        );
    }

    /**
     * test to accept email in presignup details page
     */
//    public function testPutPresignupDetailsWithEmail()
//    {
//        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');
//
//        $this->verifyOnboardingEvent('banking');
//
//        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0]);
//
//        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchant['id']]);
//
//        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
//            $merchant['id'], ['signup_via_email' => 0]
//        );
//
//        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
//
//        $this->mockHubSpotClient('trackPreSignupEvent');
//
//        $this->startTest();
//    }

    /**
     * test to rejecting email in presignup details page if signed up with email
     */
//    public function testPutPresignupDetailsWithEmailSignupViaEmail()
//    {
//        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');
//
//        $this->verifyOnboardingEvent('banking');
//
//        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 1]);
//
//        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchant['id']]);
//
//        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
//            $merchant['id'], ['signup_via_email' => 1]
//        );
//
//        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);
//
//        $this->startTest();
//    }

    /**
     * test to reject contact mobile in presignup details page if signed up with contact mobile
     */
    public function testPutPresignupDetailsWithMobileSignupViaMobile()
    {
        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->verifyOnboardingEvent('banking');

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0]);

        $merchantDetail = $this->fixtures->create('merchant_detail', ['merchant_id' => $merchant['id']]);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 0]
        );

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

//    public function testPutPresignupDetailsWithEmailExists()
//    {
//        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');
//        $testData = &$this->testData[__FUNCTION__];
//
//        $this->verifyOnboardingEvent('banking');
//
//        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 0, 'email' => 'someone@some.com']);
//        $merchant2 = $this->fixtures->create('merchant', ['signup_via_email' => 0, 'email' => 'someone@some.com']);
//
//        $merchantDetail = $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant['id'], 'contact_email' => 'someone@some.com']);
//        $merchantDetail2 = $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant2['id'], 'contact_email' => 'someone@some.com']);
//
//        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
//            $merchant['id'], ['signup_via_email' => 0]
//        );
//        $merchantUser2 = $this->fixtures->user->createBankingUserForMerchant(
//            $merchant2['id'], ['signup_via_email' => 0]
//        );
//
//        $testData["request"]["content"]["contact_email"] = $merchantDetail2["contact_email"];
//
//        $this->ba->proxyAuth('rzp_test_' . $merchantDetail2['merchant_id'], $merchantUser2['id']);
//
//        $this->startTest();
//
//    }

    protected function enableRazorXTreatmentForUniqueMobile($variant)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        if($variant === MerchantConstants::RAZORX_EXPERIMENT_ON)
        {
            $this->app->razorx->method('getTreatment')->will(
                $this->returnCallback(
                    function ($mid, $feature, $mode)
                    {
                        if ($feature === \RZP\Models\Feature\Constants::UNIQUE_MOBILE_ON_PRESIGNUP)
                        {
                            return 'on';
                        }
                        else
                        {
                            return 'control';
                        }
                    })
            );
        }
    }

    public function testPutPresignupDetailsWithContactMobileExistsUniquenessExperimentOn()
    {
        $this->enableRazorXTreatmentForUniqueMobile(MerchantConstants::RAZORX_EXPERIMENT_ON);

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');
        $testData = &$this->testData[__FUNCTION__];

        $this->verifyOnboardingEvent('banking');

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 1]);
        $merchant2 = $this->fixtures->create('merchant', ['signup_via_email' => 1]);

        $merchantDetail = $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant['id'], 'contact_mobile' => '1234567890']);
        $merchantDetail2 = $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant2['id'], 'contact_mobile' => '1234567890']);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 1]
        );
        $merchantUser2 = $this->fixtures->user->createBankingUserForMerchant(
            $merchant2['id'], ['signup_via_email' => 1]
        );

        $testData["request"]["content"]["contact_mobile"] = $merchantDetail2["contact_mobile"];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail2['merchant_id'], $merchantUser2['id']);

        $this->startTest();
    }

    public function testPutPresignupDetailsWithContactMobileExistsUniquenessExperimentOff()
    {
        $this->enableRazorXTreatmentForUniqueMobile('anything');

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 1]);

        $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant['id'], 'contact_mobile' => '1234567890']);

        $this->fixtures->user->createBankingUserForMerchant($merchant['id'], ['signup_via_email' => 1]);

        $this->testPutPreSignupDetails();

    }

    public function testPutPresignupDetailsWithContactMobileExistsWithCountryCode()
    {
        $this->enableRazorXTreatmentForUniqueMobile(MerchantConstants::RAZORX_EXPERIMENT_ON);

        $testData = &$this->testData['testPutPresignupDetailsWithContactMobileExistsUniquenessExperimentOn'];

        $merchant = $this->fixtures->create('merchant', ['signup_via_email' => 1]);
        $merchant2 = $this->fixtures->create('merchant', ['signup_via_email' => 1]);

        $merchantDetail = $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant['id'], 'contact_mobile' => '1234567890']);
        $merchantDetail2 = $this->fixtures->merchant_detail->createSane(['merchant_id' => $merchant2['id'], 'contact_mobile' => '+911234567890']);

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant(
            $merchant['id'], ['signup_via_email' => 1]
        );
        $merchantUser2 = $this->fixtures->user->createBankingUserForMerchant(
            $merchant2['id'], ['signup_via_email' => 1]
        );

        $testData["request"]["content"]["contact_mobile"] = $merchantDetail2["contact_mobile"];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail2['merchant_id'], $merchantUser2['id']);

        $this->startTest($testData);

    }

    public function testValidateInvalidCin()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testValidateCin()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testValidateInvalidLlpin()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testValidateLlpin()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testUpdateContactUniqueOwnerWithContactMobileDifferentFormatSuccess()
    {
        Mail::fake();

        [$merchantId, $userId] = $this->setupMerchantUserAndWorkflow(['contact_mobile' => '1234567890']);

        $userDb1 = $this->getDbEntityById('user',  $userId);

        $primaryMids = $userDb1->getPrimaryMerchantIds();

        for ($i = 0; $i < sizeof($primaryMids); $i++)
        {
            if ($primaryMids[$i] != $merchantId)
            {
                $merchantDetail =  $this->fixtures->merchant_detail->createAssociateMerchant([
                    'merchant_id' => $primaryMids[$i],
                    'contact_mobile' => '123456789' . $i,
                    'contact_email' => 'user'. $i. '@email.com',
                ]);
            }
        }

        $user = $this->getDbEntityById('user', $userId);

        $user->setAttribute(Entity::CONTACT_MOBILE, '1234567890');

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->ba->adminAuth();

        $this->startTest();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertNotEmpty($workflowAction);

        $workflowActionId = $workflowAction['id'];

        $this->esClient->indices()->refresh();

        $this->performWorkflowAction($workflowActionId, true);

        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);

        $user = $this->getDbEntityById('user', $userId);

        $this->assertEquals('+919876543210', $merchantDetails->getContactMobile());

        $this->assertEquals('9876543210', $user->getContactMobile());

        foreach ($primaryMids as $mid)
        {
            $merchantDetailDb = $this->getDbEntityById('merchant_detail', $mid);

            $this->assertEquals($merchantDetailDb->getContactMobile(), "+919876543210");
        }

        Mail::assertQueued(MerchantDashboardEmail::class, function ($mail) use($merchant)
        {
            $data = $mail->viewData;

            $this->assertEquals('+911234567890', $data['old_contact_number']);

            $this->assertEquals('9876543210', $data['new_contact_number']);

            $this->assertEquals('emails.merchant.update_merchant_contact_from_admin', $mail->view);

            $mail->hasTo($merchant['email']);

            return true;
        });
    }

    public function testUpdateContactMultipleOwnersSameContactMobileWithDifferentFormatFailure()
    {
        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant['id'];

        $user1 = $this->fixtures->create('user', [
            'contact_mobile'          => '9876543210',
            'contact_mobile_verified' => true,
        ]);

        $user2 = $this->fixtures->create('user', [
            'contact_mobile'          => '9876543210',
            'contact_mobile_verified' => true,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user1['id'],
                                                             'merchant_id' => $merchantId,
                                                             'role'        => 'owner',
                                                         ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user2['id'],
                                                             'merchant_id' => $merchantId,
                                                             'role'        => 'owner',
                                                         ]);

        $predefinedMerchantDetails = array_merge(
            ['merchant_id'  => $merchantId],
            ['contact_mobile' => '9876543210']
        );

        $this->fixtures->create('merchant_detail', $predefinedMerchantDetails);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId/mobile";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateMerchantContactWithContactAlreadyExistsWithDifferentFormatFailure()
    {
        $merchant1 = $this->fixtures->create('merchant');

        $merchantId1 = $merchant1['id'];

        $user = $this->fixtures->create('user', [
            'contact_mobile'          => '9876543210',
            'contact_mobile_verified' => true,
        ]);

        $this->fixtures->user->createUserMerchantMapping([
                                                             'user_id'     => $user['id'],
                                                             'merchant_id' => $merchantId1,
                                                             'role'        => 'owner',
                                                         ]);

        $predefinedMerchantDetails = ['contact_mobile' => '1234567890'];

        [$merchantId2, $userId2] = $this->setupMerchantWithMerchantDetails([], $predefinedMerchantDetails);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchants/$merchantId1/mobile";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAddGstinSelfServeValidationFailWorkflowNeedsClarification()
    {
        Mail::fake();

        Config(['services.bvs.mock' => true]);

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        extract($this->setupMerchantForGstinSelfServeTest());

        $this->mockStorkForAddGstinSelfServeValidationFailWorkflowNeedClarification();

        $this->setupWorkflow('edit_gstin_details', 'edit_merchant_gstin_detail');

        $this->updateUploadDocumentData(__FUNCTION__, 'gstin_self_serve_certificate');

        $this->startTest();

        $this->processBvsResponseForGstinSelfServe('failed');

        $this->esClient->indices()->refresh();

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->assertWorkflowDataForGstInSelfServe($workflowAction);

        $testData = $this->testData['testNeedClarificationOnWorkflow'];

        $testData['request']['url'] = '/merchant/' . $workflowAction['id'] . '/need_clarification';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();

        $request = [
            'method' => 'GET',
            'url'    => '/w-actions/' . $workflowAction['id'] . '/details',
            'content' => []
        ];

        $res = $this->makeRequestAndGetContent($request);

        $this->assertEquals($res['id'], $workflowAction['id']);

        $this->assertEquals('awaiting-customer-response', $res['tagged'][0]);

        $this->assertWorkflowNeedsClarificationMailQueued('https://dashboard.razorpay.com/app/profile/clarification_update_gstin');

    }

    protected function mockStorkForAddGstinSelfServeValidationFailWorkflowNeedClarification()
    {
        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $expectedStorkParametersForTemplate = [
            'merchant_name'  => 'Test name'
        ];

        $this->expectStorkSendSmsRequest($storkMock,'sms.dashboard.merchant_add_gstin_needs_clarification_V1', '1234567890', $expectedStorkParametersForTemplate);

        $this->expectStorkWhatsappRequest($storkMock,
            'Hi Test name, we need a few more details to process the request on adding your GSTIN to Razorpay account. Please click https://dashboard.razorpay.com/app/profile/clarification_update_gstin to share the details.
            -Team Razorpay',
            '1234567890'
        );
    }

    private function mockSplitzEvaluation() {

        $input = [
            "experiment_id" => "JIRYzx7YtMuB18",
            "id"            => "10000000000000",
            "request_data"  => "{\"id\":\"10000000000000\"}",
        ];

        // push metrics experiment, hence not enabling
        $output = [
            "response" => [
                "variant" => null
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        //experiment not relevant, hence not enabling
        $input = [
            "experiment_id" => "JmNwFyivyRzcg3",
            "id" => '10000000000000',
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => null
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
    }

    public function testSaveWebsitePlugin()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $website = 'https://flipkart.com';

        $this->fixtures->on('live')->edit('merchant', $merchantId, ['website' => $website, 'whitelisted_domains' => ['flipkart.com']]);

        $this->fixtures->on('live')->create('merchant_detail', ['merchant_id' => $merchantId, 'business_website' => $website, 'issue_fields' => 'business_website']);

        $this->fixtures->on('live')->create('merchant_business_detail', ['merchant_id' => $merchantId]);

        $this->ba->proxyAuth();

        $this->startTest();

        $businessDetail = $this->getLastEntity('merchant_business_detail', true);

        $expectedData = [
            [
                'website' => 'https://flipkart.com',
                'merchant_selected_plugin' => 'wix'
            ]
        ];

        $this->assertEquals($expectedData, $businessDetail['plugin_details']);
    }

    public function testSaveWebsitePluginExistingWebsite()
    {
        $merchantId = '1cXSLlUU8V9sXl';

        $website = 'https://flipkart.com';

        $this->fixtures->on('live')->edit('merchant', $merchantId, ['website' => $website, 'whitelisted_domains' => ['flipkart.com']]);

        $this->fixtures->on('live')->create('merchant_detail', ['merchant_id' => $merchantId, 'business_website' => $website, 'issue_fields' => 'business_website']);

        $this->fixtures->on('live')->create('merchant_business_detail', [
            'merchant_id' => $merchantId,
            'plugin_details' => [
                'website' => 'https://flipkart.com',
                'merchant_selected_plugin' => 'WooCommerce'
            ]
        ]);

        $this->ba->proxyAuth();

        $testData = $this->testData['testSaveWebsitePlugin'];

        $this->runRequestResponseFlow($testData);

        $businessDetail = $this->getLastEntity('merchant_business_detail', true);

        $expectedData = [
            [
                'website' => 'https://flipkart.com',
                'merchant_selected_plugin' => 'wix'
            ]
        ];

        $this->assertEquals($expectedData, $businessDetail['plugin_details']);
    }

    public function testMerchantWebsitePluginResult()
    {
        Config::set('services.whatCMS.mock', true);

        $merchantId = '10000000000001';

        $website = 'www.liotec.ch';

        $this->fixtures->create('merchant', ['id' => $merchantId,
                                              'website' => $website,
                                              'whitelisted_domains' => ['www.liotec.ch']]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'business_website' => $website, 'issue_fields' => 'business_website']);

        $this->fixtures->create('merchant_business_detail', ['merchant_id' => $merchantId]);

        $kafkaEventPayload = [
            'merchant_id'=>$merchantId,
            'website_url'=>'www.liotec.ch'
        ];

        (new KafkaMessageProcessor)->process('merchant-website-info-result', $kafkaEventPayload, 'live');

        $businessDetail = $this->getDbLastEntity('merchant_business_detail', 'live');

        $expectedData = [
            [
                'website' => 'www.liotec.ch',
                'suggested_plugin' => 'DummyTestPluginType',
                'ecommerce_plugin' => true
            ]
        ];

        $this->assertEquals($expectedData, $businessDetail['plugin_details']);
    }

    public function testMerchantWebsitePluginProducerCalled()
    {
        Config::set('services.kafka.producer.mock', true);

        $merchantId = '10000000000000';

        $website = 'www.liotec.ch';

        $this->setMockRazorxTreatment(['WHATCMS_EXPERIMENT' => 'on']);

        $kafkaEventPayload = [
            'merchant_id'=>'IY31FYZ48vP1vc',
            'website_url'=>'www.liotec.ch'
        ];

        $kafkaProducerMock = $this->getMockBuilder(KafkaProducerClient::class)
                                  ->onlyMethods(['produce'])
                                  ->getMock();
        $response = [
            'topicName' => 'merchant-website-info-result',
            'message' => $kafkaEventPayload
        ];

        $kafkaProducerMock->method('produce')
             ->willReturn($response);

        $this->app->instance('kafkaProducerClient', $kafkaProducerMock);

        $kafkaProducerMock->expects($this->once())->method('produce')->withAnyParameters();

        $this->fixtures->on('live')->edit('merchant', $merchantId, ['website' => $website]);
    }

    public function testMerchantWebsitePluginResultForEmptyWebsite()
    {
        $kafkaEventPayload = [
            'merchant_id'=>'10000000000000',
            'website_url'=>''
        ];

        (new KafkaMessageProcessor)->process('merchant-website-info-result', $kafkaEventPayload, 'live');

        $businessDetail = $this->getDbLastEntity('merchant_business_detail', 'live');

        $this->assertNull($businessDetail);
    }

    public function testFetchIdentityVerificationUrl()
    {
        Config::set('services.bvs.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->create('stakeholder', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $stakeholderDetail = $this->getDbLastEntity('stakeholder');

        $this->assertTrue(isset($stakeholderDetail['verification_metadata']) === true);

        $this->assertTrue(isset($stakeholderDetail['verification_metadata']['reference_id']) === true);
    }

    public function testFetchIdentityVerificationUrlForInvalidInputs()
    {
        $merchant = $this->fixtures->on('test')->create('merchant', [
            'activated'  => 1,
        ]);

        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields', [
            'merchant_id'       => $merchant['id'],
            'activation_status' => 'activated_mcc_pending',
            ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->startTest();

    }

    private function mockSplitzExperiment($output)
    {
        $this->splitzMock = \Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->byDefault()
            ->andReturn($output);
    }

    public function testSaveMerchantConsentNotProvided()
    {
        Config::set('services.bvs.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();
    }

    public function testCreateMerchantProductDuringMerchantActivationIfNotExist()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => 'enable', ]]]);

        $this->startTest();

        $merchantProducts = $this->getDbEntities('merchant_product', [
            'merchant_id' => $merchant->getId(),
            'product_name' => 'payment_gateway']);

        $this->assertEquals(1, count($merchantProducts));
    }

    public function testSkipCreateMerchantProductDuringMerchantActivationIfExist()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => 'enable', ]]]);

        $this->startTest();

        $merchantProducts = $this->getDbEntities('merchant_product', [
            'merchant_id' => $merchant->getId(),
            'product_name' => 'payment_gateway']);

        $this->assertEquals(1, count($merchantProducts));
    }

    public function testErrorWhenInvalidPartnerIdIsProvidedDuringActivation()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => 'enable', ]]]);

        $this->startTest();
    }

    public function testSaveMerchantConsentProvided()
    {
        Config::set('services.bvs.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $consentDetail = $this->getDbLastEntity('merchant_consents', 'test');

        $this->assertEquals($merchantId, $consentDetail['merchant_id']);

        $this->assertEquals('initiated', $consentDetail['status']);
    }

    public function testErrorWhenExpIsNotEnabledForProvidedPartnerIdDuringActivation()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->mockSplitzExperiment(["response" => ["variant" => ["name" => null, ]]]);

        $this->startTest();
    }

    public function testFetchIdentityVerificationUrlForBVSFailure()
    {
        Config::set('services.bvs.mock', true);

        Config::set('services.bvs.response', 'failed');

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->create('stakeholder', ['merchant_id' => $merchantId]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $stakeholderDetail = $this->getDbLastEntity('stakeholder');

        $this->assertTrue(isset($stakeholderDetail['verification_metadata']) === true);
    }

    public function testProcessIdentityVerificationDetails()
    {
        Config::set('services.bvs.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->create('stakeholder', [
            'merchant_id' => $merchantId,
            'verification_metadata' =>  ['reference_id' => 'reference_id']
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $stakeholderDetail = $this->getDbLastEntity('stakeholder');

        $this->assertTrue(isset($stakeholderDetail['bvs_probe_id']) === true);

        $this->assertEquals('verified', $stakeholderDetail['aadhaar_esign_status']);
    }

    public function testFailureProcessIdentityVerificationDetails()
    {
        Config::set('services.bvs.mock', true);

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->create('stakeholder', [
            'merchant_id' => $merchantId
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $stakeholderDetail = $this->getDbLastEntity('stakeholder');

        $this->assertTrue(isset($stakeholderDetail['bvs_probe_id']) === false);

        $this->assertTrue(isset($stakeholderDetail['aadhaar_esign_status']) === false);
    }

    public function testProcessIdentityVerificationDetailsForBVSFailure()
    {
        Config::set('services.bvs.mock', true);

        Config::set('services.bvs.response', 'failed');

        $merchant = $this->fixtures->create('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId]);

        $this->fixtures->create('stakeholder', [
            'merchant_id' => $merchantId,
            'verification_metadata' =>  ['reference_id' => 'reference_id']
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $merchantUser['id']);

        $this->startTest();

        $stakeholderDetail = $this->getDbLastEntity('stakeholder');

        $this->assertTrue(isset($stakeholderDetail['bvs_probe_id']) === false);

        $this->assertTrue(isset($stakeholderDetail['aadhaar_esign_status']) === false);
    }

    public function testErrorWhenResellerPartnerProvidedDuringActivation()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testErrorWhenPurePlatformPartnerProvidedDuringActivation()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'pure_platform']);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testErrorWhenPartnerIdProvidedIsNotPartnerDuringActivation()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => null]);

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => $merchant['id'],
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testMerchantObserverPaymentsEditLiveMode()
    {
        $this->app['rzp.mode'] = 'live';

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->Exactly(1))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $eventAttributes, $eventName) {
                        if ($eventName === "Merchant Funds And Payment Status")
                        {
                            $this->assertTrue(array_key_exists("activated", $eventAttributes));
                            $this->assertTrue(array_key_exists("live", $eventAttributes));
                            $this->assertTrue(in_array($eventName, ["Merchant Funds And Payment Status"], true));
                        }
                    }));

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type' => 4,
            'submitted'     => 1,
        ]);

        $merchant = $merchantDetail->merchant;

        $this->app['basicauth']->setMerchant($merchant);

        $this->assertFalse($merchant->isActivated());

        $this->fixtures->merchant->edit($merchantDetail->getId(), ['activated' => true, 'live' => true]);

        $merchant = $this->getDbLastEntity('merchant');

        $this->assertTrue($merchant->isActivated());

    }

    public function testSkipBankVerificationInCaseOfVerifiedMerchantAccount()
    {
        $merchant = $this->fixtures->create('merchant',[
            'hold_funds' => true
        ]);

        $mid = $merchant->getId();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'                      => $mid,
            'bank_details_verification_status' => 'verified',
        ]);

        $action = 'release_funds';

        $response = (new \RZP\Models\RiskWorkflowAction\Core())->validateMerchantForAction($action, $merchant);

        // We do not want to assert anything , as it is a validation check and in case of bank details verified
        // we do not want to throw any error and simply return from the function.

        $this->assertNull($response);
    }
}
