<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Config;
use Mockery;

use RZP\Constants;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\EsDao;
use RZP\Models\Merchant\Core;
use RZP\Models\User\Role;
use RZP\Services\DiagClient;
use RZP\Services\RazorXClient;
use RZP\Services\HubspotClient;
use RZP\Mail\Merchant\Rejection;
use Functional\Helpers\BvsTrait;
use Illuminate\Http\UploadedFile;
use RZP\Services\SalesForceClient;
use RZP\Error\PublicErrorDescription;
use RZP\Mail\Merchant as MerchantMail;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Exception\ServerErrorException;
use RZP\Models\Merchant\Document\Source;
use RZP\Mail\Merchant\MerchantDashboardEmail;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Mail\Merchant\MerchantBusinessWebsiteAdd;
use RZP\Mail\Merchant\RejectionReasonNotification;
use RZP\Models\Merchant\Detail\BusinessSubcategory;
use RZP\Mail\Merchant\MerchantBusinessWebsiteUpdate;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Mail\Merchant\GstinSelfServeVerificationFailure;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Merchant\Bvs\BvsValidationTest;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\Document\Entity as MerchantDocuments;
use RZP\Models\Workflow\Action\Repository as ActionRepository;
use RZP\Mail\Merchant\RazorpayX\AccountActivationConfirmation;
use RZP\Models\Admin\Permission\Repository as PermissionRepository;


class MerchantDetailTest extends OAuthTestCase
{
    use BvsTrait;
    use RazorxTrait;
    use PaymentTrait;
    use TerminalTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use WorkflowTrait;


    const PARTNER                = 'partner';
    const ACTIVATION             = 'activation';
    const DEACTIVATION           = 'deactivation';
    const DUMMY_APP_ID_1         = '8ckeirnw84ifke';
    const DUMMY_APP_ID_2         = '10000RandomApp';
    const DUMMY_APP_ID_3         = '11111RandomApp';
    const DEFAULT_MERCHANT_ID    = '10000000000000';
    const DEFAULT_SUBMERCHANT_ID = '10000000000009';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantDetailTestData.php';

        parent::setUp();

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();

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

    public function testDefaultInstrumentRequestOnMerchantActivation(){

        $merchantId = '1cXSLlUU8V9sXl';

        $website = 'http://abc.com';

        $this->fixtures->edit('merchant', $merchantId, ['website' => $website, 'whitelisted_domains' => ['abc.com']]);

        $this->fixtures->create('merchant_detail', ['merchant_id' => $merchantId, 'business_website' => $website, 'issue_fields' => 'business_website', 'submitted'=>true]);

        $this->fixtures->on('live')->create('methods:default_methods', [
            'merchant_id' => '1cXSLlUU8V9sXl'
        ]);


        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'instrument_request_merchant_dashboard')
                    {
                        return 'on';
                    }
                    else
                    {
                        return 'control';
                    }

                }) );

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $expectedResponse = [
            'methods' => 'POST',
            'content' => '{"merchant_id":"1cXSLlUU8V9sXl"}',
            'path'    => 'v2/default_merchant_instrument_requests',
        ];

        $receivedMethod = '';

        $receivedContent = '';

        $receivedPath = '';

        // cannot assert within mock as exception failures thrown are handled in code somewhere else, leading to silent failure of assertions failures
        $this->mockTerminalsServiceSendRequest(function($path, $content, $method) use (&$receivedPath, &$receivedContent, &$receivedMethod) {

            $receivedMethod = $method;

            $receivedContent = $content;

            $receivedPath = $path;

        }, 1);

        $merchantId = '1cXSLlUU8V9sXl';

        $this->startTest();


        $this->assertEquals($expectedResponse['methods'], $receivedMethod);

        $this->assertEquals($expectedResponse['path'], $receivedPath);

        $this->assertEquals($expectedResponse['content'], $receivedContent);


        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $this->assertTrue($merchant->isActivated());

    }

    protected function changeActivationStatusFromUnderReviewToNeedsClarification(& $requestContent, & $responseContent)
    {
        $requestContent['activation_status'] = 'needs_clarification';

        $requestContent['clarification_mode'] = 'email';

        $responseContent['activation_status'] = 'needs_clarification';

        $responseContent['clarification_mode'] = 'email';

        $responseContent['locked']             = false;
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

    public function testMerchantDetailsFetchAccountService()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', ['business_category' => 'financial_services']);
        $merchant       = $merchantDetail->merchant;

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_email', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId()]);
        $this->fixtures->create('merchant_document', ['merchant_id' => $merchant->getId(), 'entity_type' => 'stakeholder', 'document_type' => 'aadhar_front']);

        // test fetch account details by account service
        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/account_service/accounts/'. $merchant->getId();
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
            'payzapp'       => true,
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

        $this->mockRavenAndStorkForBusinessWebsiteAdd();

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

    protected function mockRavenAndStorkForBusinessWebsiteAdd()
    {
        $ravenMock = Mockery::mock('RZP\Services\Raven', [$this->app])->makePartial();

        $this->app->instance('raven', $ravenMock);

        $expectedRavenParametersForTemplate = [
            'updated_business_website' => 'https://www.example.com'
        ];

        $this->expectRavenSendSmsRequest($ravenMock,'sms.dashboard.merchant_business_website_add', '1234567890', $expectedRavenParametersForTemplate);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

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
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailData);

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000155');

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

    public function testPutPreSignupDetails()
    {
        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $this->verifyOnboardingEvent('banking');

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->mockHubSpotClient('trackPreSignupEvent');

        $this->startTest();
    }

    public function testPutPreSignupDetailsForNeostone()
    {
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

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->mockHubSpotClient('trackPreSignupEvent');

        $this->mockSalesforceEventTracked('sendPreSignupDetails');

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
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testVaCreationTestModeInPreSignup()
    {
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

    public function testBulkEditMerchantAttributes()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        // Put CSV file as UploadedFile instance in request
        $path = __DIR__ . '/helpers/bulk-edit-merchant-attributes.csv';
        $file = new UploadedFile($path, 'file.csv', 'text/csv', filesize($path), null, true);
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
            filesize(__DIR__ . '/../Storage/a.png'),
            null,
            true);
    }

    public function testUpdateKYCClarificationReason()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/activation/$merchantId/update";

        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->startTest();
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

        $appType = \RZP\Models\Merchant\MerchantApplications\Entity::REFERRED;

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
    }

    public function testPutPreSignUpDetailsWithBankingReferralCodeInX()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $app = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'reseller']);

        $appType = \RZP\Models\Merchant\MerchantApplications\Entity::REFERRED;

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
    }

    public function testPutPreSignUpDetailsWithPrimaryReferralCodeInX()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

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

    public function testPutPreSignUpDetailsWithReferralCodeForAggregator()
    {
        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, ['partner_type' => 'aggregator']);

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $managedApp = $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => 'aggregator'], true);

        $referredApp = $this->fixtures->merchant->createDummyReferredAppForManaged(['partner_type' => 'reseller'], true);

        $appType = \RZP\Models\Merchant\MerchantApplications\Entity::REFERRED;

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

        $configAttributes = [
            'default_plan_id' => self::DEFAULT_MERCHANT_ID,
            'entity_id'       => $referredApp->getId(),
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

        $merchantApp = $this->getDbEntity('merchant_application', ['application_id' => $referredApp->getId()]);

        $mapping = DB::table('merchant_users')->where('merchant_id', '=', self::DEFAULT_SUBMERCHANT_ID)
                       ->where('user_id', '=', $referrerId)
                       ->get();

        $this->assertEmpty($mapping);

        $this->assertEquals($merchantApp->type, $appType);

        $this->assertEquals($referredSubMerchant->tagNames(), array('Ref-' . $referrerId));

        $this->assertEquals($referredSubMerchant->getPricingPlanId(), self::DEFAULT_MERCHANT_ID);

        $this->assertSame($referredSubMerchantId, $merchantAcessMap['merchant_id']);

        $this->assertSame($referrerId, $merchantAcessMap['entity_owner_id']);

        $this->assertSame($referredApp->getId(), $merchantAcessMap['entity_id']);
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

    public function testUpdateGstinSelfServe()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $this->assertGstinSelfServeStatus('not_started');

        $this->startTest();

        $data = $this->app['cache']->get('gstin_self_serve_input_' . $merchant['id']);

        $this->assertEquals([
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => 'Registered Address',
            'business_registered_state'   => 'DL',
            'business_registered_city'    => 'Delhi',
            'business_registered_pin'     => '560050',
        ], $data);

        $this->assertGstinSelfServeStatus('in_progress');
    }

    public function testUpdateGstinSelfServeWhenInProgressShouldFail()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        $this->startTest();

        $this->assertGstinSelfServeStatus('in_progress');

        // changing the content and asserting that the data in cache didnt get over-written(changes to address, state and pin)

        $this->testData[__FUNCTION__]['request']['content'] = [
            'gstin'                       => '18AABCU9603R1ZN',
            'business_registered_address' => 'random Address',
            'business_registered_state'   => 'KA',
            'business_registered_city'    => 'Karnataka',
            'business_registered_pin'     => '560030',
        ];

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('already in progress');

        $this->startTest();

        $data = $this->app['cache']->get('gstin_self_serve_input_10000000000000');

        // asserting only data created in first call is stored
        $this->assertEquals([
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => 'Registered Address',
            'business_registered_state'   => 'DL',
            'business_registered_city'    => 'Delhi',
            'business_registered_pin'     => '560050',
        ], $data);
    }

    public function testUpdateGstinSelfServeInvalidUserRole()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

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

    public function testUpdateGstinSelfServeV2()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $this->initiateGstinSelfServeV2();

        $merchantDetail = $this->getLastEntity('merchant_detail', true);

        $requestToBvs = $this->app['cache']->get('unittest_bvs_validation_array');

        $artefactToBvs = $requestToBvs['artefact'];

        $rulesListToBvs = $requestToBvs['rules']['rules_list'];

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsProcessValidationHandler = $this->app['cache']->get('bvs_validation_custom_process_validation_' . $bvsValidationEntity['validation_id']);

        $this->assertArraySelectiveEquals([
            'gstin'                       => null,
            'business_registered_address' => null,
            'business_registered_state'   => null,
            'business_registered_city'    => null,
            'business_registered_pin'     => null,
            ], $merchantDetail); // asserting old values still present


        $this->assertArraySelectiveEquals([
            'owner_id'   => $merchant['id'],
            'owner_type' => 'merchant',
            'type'       => 'gstin',
            'details'    => [
                'gstin'            => '18AABCU9603R1ZM',
                'legal_name'       => 'randomLegalName',
                'trade_name'       => 'randomTradeName',
                'primary_pin_code' => '560050',
            ],
        ], $artefactToBvs);

        $this->assertCount(3, $rulesListToBvs);

        // explicity asserting for pincode rule
        $pincodeRule = $rulesListToBvs[2];

        $this->assertEquals([
            "rule_type" => "string_comparison_rule",
            "rule_def"  => [
                "equals" => [
                    [
                        "var" => "artefact.details.primary_pin_code.value",
                    ],
                    [
                        "var" => "enrichments.online_provider.details.primary_pin_code.value",
                    ],
                ],
            ],
        ], $pincodeRule);

        $this->assertArraySelectiveEquals([
            'owner_id'          => $merchant['id'],
            'owner_type'        => 'merchant',
            'validation_status' => 'captured',
            'artefact_type'     => 'gstin',
        ], $bvsValidationEntity);

        $this->assertEquals('GstinSelfServeCallbackHandler', $bvsProcessValidationHandler);
    }

    public function testUpdateGstinSelfServeV2BvsValidationCreationError()
    {
        $this->setupMerchantForGstinSelfServeTest();

        Config::set('services.bvs.response', 'failure');

        $this->expectException(ServerErrorException::class);

        $this->initiateGstinSelfServeV2();
    }

    public function testGstinSelfServeBvsCallback()
    {
        extract($this->setupMerchantForGstinSelfServeTest());

        $this->initiateGstinSelfServeV2();

        $this->assertGstinSelfServeStatus('in_progress');

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id']);

        $this->processBvsResponseAndValidate($bvsResponse, $bvsValidationEntity['validation_id']);

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $cacheData = $this->app['cache']->get('gstin_self_serve_input_' . $merchant['id']);

        $this->assertNull($cacheData);

        $this->assertArraySelectiveEquals([
            'gstin'                       => '18AABCU9603R1ZM',
            'business_registered_address' => 'Registered Address',
            'business_registered_state'   => 'DL',
            'business_registered_city'    => 'Delhi',
            'business_registered_pin'     => '560050',
        ], $merchantDetail);

        Mail::assertNotQueued(GstinSelfServeVerificationFailure::class);

        $this->ba->proxyAuth('rzp_test_' . $merchant['id'], $user['id']);

        $this->assertGstinSelfServeStatus('not_started');
    }

    public function testGstinSelfServeBvsCallbackVerificationFailure()
    {
        $merchant = $this->setupMerchantForGstinSelfServeTest()['merchant'];

        $this->fixtures->edit('merchant_detail', $merchant['id'], [
            'business_registered_address' => 'old business address',
            'business_registered_state'   => 'KA',
            'business_registered_city'    => 'bangalore',
            'business_registered_pin'     => '560040',
            'merchant_id'                 => $merchant['id'],
            'promoter_pan_name'           => 'randomLegalName',
            'business_name'               => 'randomTradeName',
        ]);

        $this->initiateGstinSelfServeV2();

        $bvsValidationEntity = $this->getDbLastEntity('bvs_validation')->toArray();

        $bvsResponse = $this->getBvsResponse($bvsValidationEntity['validation_id'], 'failed', 'NO_PROVIDER_ERROR');

        $this->processBvsResponse($bvsResponse);

        $cacheData = $this->app['cache']->get('gstin_self_serve_input_' . $merchant['id']);

        $this->assertNull($cacheData);

        Mail::assertQueued(GstinSelfServeVerificationFailure::class, function($mail) {
           return true;
        });

        $this->assertGstinSelfServeStatus('not_started');

        $merchantDetail = $this->getEntityById('merchant_detail', $merchant['id'], true);

        $this->assertArraySelectiveEquals([
            'gstin'                       => null,
            'business_registered_address' => 'old business address',
            'business_registered_state'   => 'KA',
            'business_registered_city'    => 'bangalore',
            'business_registered_pin'     => '560040',
        ], $merchantDetail); // asserting old values still present
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
        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] = config('applications.banking_service_url');

        $param  = ["product_type" => "banking"];

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createBankingUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $this->mockHubSpotClientForProductType('dispatchRequestJob', $param);

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

    protected function assertGstinSelfServeStatus(string $expectedStatus)
    {
        $data = $this->testData['getSelfServeGetStatus'];

        $data['response']['content']['status'] = $expectedStatus;

        $this->startTest($data);
    }

    protected function setGstinSelfServeV2Flow()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'gstin_self_serve_v2')
                    {
                        return 'on';
                    }
                    return 'control';
                }));
    }

    protected function setupMerchantForGstinSelfServeTest()
    {
        Mail::fake();

        $merchant = $this->fixtures->merchant->create();

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant['id'],
            'promoter_pan_name' => 'randomLegalName',
            'business_name'     => 'randomTradeName',
        ]);

        $this->fixtures->merchant->addFeatures('gstin_self_serve', $merchant['id']);

        $user = $this->fixtures->create('user');

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

    private function initiateGstinSelfServeV2()
    {
        $this->testData[__FUNCTION__] = $this->testData['testUpdateGstinSelfServe'];

        $this->setGstinSelfServeV2Flow();

        $this->startTest();
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

        return [$merchantId, $user->id];
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

    private function saveBusinessWebsiteMakerFlow(array $predefinedMerchantDetails = [], string $permissionName = PermissionName::EDIT_MERCHANT_WEBSITE_DETAIL, bool $addTestCredentials = true)
    {
        [$merchantId , $userId] = $this->setupMerchantWithMerchantDetails(['has_key_access' => true], $predefinedMerchantDetails);

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

    private function validateBusinessWebsiteWorkflowApprove($merchantId , $workflowActionId)
    {
        $this->performWorkflowAction('w_action_'.$workflowActionId, true );

        $merchant = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertEquals('https://www.example.com', $merchant->getWebsite());
    }

    private function validateBusinessWebsiteWorkflowReject($merchantId, $workflowActionId)
    {
        $rejectionReason = ['subject' => 'Test subject', 'body' => 'Test body'];

        $observerData = [ 'rejection_reason' => $rejectionReason, 'ticket_id' => '123', 'fd_instance' => 'rzp' ];

        $this->updateObserverData('w_action_' . $workflowActionId, $observerData);

        $insertedObserverData = $this->getWorkflowData();

        $this->assertNotEmpty($insertedObserverData);

        $insertedObserverData = $insertedObserverData['workflow_observer_data'];

        $expectedObserverData = ['rejection_reason' => json_encode($rejectionReason), 'ticket_id' => '123', 'fd_instance' => 'rzp' ];

        $this->assertArraySelectiveEquals($expectedObserverData, $insertedObserverData);

        $this->performWorkflowAction('w_action_'.$workflowActionId, false );

        $merchant = $this->getDbEntityById('merchant_detail', $merchantId);

        $this->assertNotEquals('https://www.example.com', $merchant->getWebsite());
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

    public function testUpdateBusinessWebsiteWorkflowApprove()
    {
        Mail::fake();

        $ravenMock = Mockery::mock('RZP\Services\Raven', [$this->app])->makePartial();

        $this->app->instance('raven', $ravenMock);

        $expectedRavenParametersForTemplate = [
            'updated_business_website'  => 'https://www.example.com',
            'previous_business_website' => 'https://www.sample.com'
        ];

        $this->expectRavenSendSmsRequest($ravenMock,'sms.dashboard.merchant_business_website_update', '1234567890', $expectedRavenParametersForTemplate);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

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

        $segmentMock->expects($this->exactly(1))
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

        $this->mockRavenAndStorkForBusinessWebsiteAdd();

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
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/w_action_'.$workflowActionId.'/decrypt_website_comment';

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBusinessWebsiteEncryptionCommentNotFound()
    {
        Mail::fake();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE , false);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/merchant/w_action_' . $workflowActionId . '/decrypt_website_comment';

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRejectionReasonMerchantNotificationForWebsiteSelfServe()
    {
        Mail::fake();


        // will uncomment once SMS and Whatsapp templates are approved

        //$this->mockRavenAndStorkForRejectionReason();

        $merchantId = $this->saveBusinessWebsiteMakerFlow(['business_website'=> 'https://www.sample.com', 'activation_status' => 'activated'], PermissionName::UPDATE_MERCHANT_WEBSITE);

        [$merchantId, $workflowActionId] = $this->validateBusinessWebsiteWorkflow($merchantId, PermissionName::UPDATE_MERCHANT_WEBSITE);

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

    protected function mockRavenAndStorkForRejectionReason()
    {
        $ravenMock = Mockery::mock('RZP\Services\Raven', [$this->app])->makePartial();

        $this->app->instance('raven', $ravenMock);

        $expectedRavenParametersForTemplate = [
            'messageBody' => 'Test body'
        ];

        $this->expectRavenSendSmsRequest($ravenMock,'sms.dashboard.rejection_reason_notification', '1234567890', $expectedRavenParametersForTemplate);

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->expectStorkWhatsappRequest($storkMock,
            'Test body
-Team Razorpay',
            '1234567890'
        );
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
                $response = new \Requests_Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

    protected function expectRavenSendSmsRequest($ravenMock, $templateName, $receiver, $expectedParms = [])
    {
        $ravenMock->shouldReceive('sendSms')
            ->times(1)
            ->with(
                Mockery::on(function ($actualPayload) use ($templateName, $receiver, $expectedParms)
                {
                    $this->assertArraySelectiveEquals($expectedParms, $actualPayload['params']);

                    if (($templateName !== $actualPayload['template']) or
                        ($receiver !== $actualPayload['receiver']))
                    {
                        return false;
                    }

                    return true;
                }),  Mockery::on(function ($mockInTestMode)
            {
                if ($mockInTestMode === true)
                {
                    return false;
                }
                return true;
            }))
            ->andReturnUsing(function ()
            {
                return ['sms_id' => '10000000000sms'];
            });
    }

    public function testAddAdditionalWebsiteSelfServeWorkflowApprove()
    {
        $predefinedMerchant = [
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

        $websites = $this->getCollectionOfWebsites(5);

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

    public function testAddAdditionalApp()
    {
        $predefinedMerchant = [
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

        $testData = & $this->testData['testPgKycActivation'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->fixtures->on('live')->edit('terminal','BANKACC3DSN3DT',
            ['gateway_merchant_id' => '3434']);

        $this->fixtures->on('live')->edit('terminal','BANKACC3DSN3DZ',
            ['gateway_merchant_id' => '232323']);

        $this->fixtures->on('test')->edit('terminal','BANKACC3DSN3DT',
            ['gateway_merchant_id' => '3434']);

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
}
