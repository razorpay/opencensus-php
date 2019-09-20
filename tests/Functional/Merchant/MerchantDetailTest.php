<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use RZP\Constants;
use Illuminate\Http\UploadedFile;
use RZP\Services\HubspotClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\Detail\BusinessSubcategory;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Models\Merchant\Detail\Entity as MerchantDetails;
use RZP\Models\Merchant\Document\Entity as MerchantDocuments;

/**
 * @group dns-sensitive
 */
class MerchantDetailTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;
    use MocksDnsTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantDetailTestData.php';

        parent::setUp();

        $this->setupMockDns();
    }

    public function testGetMerchantDetails()
    {
        $merchant = $this->fixtures->create('merchant:with_keys');

        $user = $this->fixtures->user->createUserForMerchant($merchant['id']);

        $this->ba->proxyAuth('rzp_test_' .$merchant['id'], $user->getId());

        $this->startTest();
    }

    public function testUpdateIfscCode()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testSubmit()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        $this->mockHubSpotClient('trackL2ContactProperties');

        $this->startTest();
    }

    public function testSubmitAutoActivate()
    {
        $this->fixtures->merchant->addFeatures(['marketplace'], '10000000000000');

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $this->fixtures->edit('merchant', $merchantId, ['linked_account_kyc' => 0, 'parent_id' => '10000000000000']);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testSubmitWithInvalidFields()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:invalid_fields');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateIfscCodeWithFailure()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateEmail()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateEmails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateEmailWithFailure()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testUpdateDetailForLockedMerchant()
    {
        $attribute = ['locked' => true];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id']);

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
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

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
    }

    protected function changeActivationStatusFromUnderReviewToNeedsClarification(& $requestContent, & $responseContent)
    {
        $requestContent['activation_status'] = 'needs_clarification';

        $requestContent['clarification_mode'] = 'email';

        $responseContent['activation_status'] = 'needs_clarification';

        $responseContent['clarification_mode'] = 'email';
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
                'reason_category' => 'risky_business',
                'reason_code'     => 'refurbished_goods',
            ],
            [
                'reason_category' => 'risky_business',
                'reason_code'     => 'gift_cards',
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

        $this->ba->adminProxyAuth($merchant->getId());

        $this->startTest();

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchant->getId());

        $this->assertEquals($merchantDetails->getInternationalActivationFlow(), 'whitelist');

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

        $this->ba->adminProxyAuth($merchant->getId());

        $this->startTest();
    }

    public function testMerchantDetailsPatchInvalidInternationalActivtionFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');
        $merchant = $merchantDetail->merchant;

        // Allow admin to access the merchant
        $admin = $this->ba->getAdmin();
        $admin->merchants()->attach($merchant);

        $this->ba->adminProxyAuth($merchant->getId());

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

        $this->ba->adminProxyAuth($merchant->getId());

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

        $this->ba->adminProxyAuth($merchant->getId());

        $this->startTest();
    }

    public function testMerchantUpdateWebsiteDetails()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $this->ba->proxyAuth('rzp_test_'.$merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals($merchant->getWebsite(), 'https://www.example.com');
        $this->assertEquals($merchant->getHasKeyAccess() , true);
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
        $merchant = $this->fixtures->create('merchant:with_keys');

        $this->ba->proxyAuth('rzp_test_' .$merchant['id']);

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

        $this->ba->proxyAuth('rzp_live_10000000000155');

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
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_live_'.$merchantDetail['merchant_id']);

        $this->mockHubSpotClient('trackPreSignupEvent');

        $this->startTest();
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

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

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

        $this->ba->proxyAuth('rzp_live_' . $merchantDetail['merchant_id']);

        $this->startTest();
    }

    public function testBulkAssignReviewer()
    {
        $this->ba->adminAuth();

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
        $this->assertSame('Kerala', $testMerchant->merchantDetail->getBusinessRegisteredState());
        $this->assertSame('kerala@test.com', $testMerchant->merchantDetail->getContactEmail());

        $liveMerchant = $this->getDbEntityById('merchant', '10000000000000', 'live');
        $this->assertSame('Kerala', $liveMerchant->merchantDetail->getBusinessRegisteredState());
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

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

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

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

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
     * checks that category and category2 details should be set on business category changed to others
     * this is a special case as business subcategory field will be null
     */
    public function testCategoryDetailsSetForOthersCategory()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            MerchantDetails::BUSINESS_SUBCATEGORY => BusinessSubcategory::LENDING,
            MerchantDetails::BUSINESS_CATEGORY    => BusinessCategory::FINANCIAL_SERVICES,
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->startTest();

        $liveMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'live');
        $this->assertSame('5399', $liveMerchant->getCategory());
        $this->assertSame('others', $liveMerchant->getCategory2());

        $testMerchant = $this->getDbEntityById('merchant', $merchantDetail[MerchantDetails::MERCHANT_ID], 'test');
        $this->assertSame('5399', $testMerchant->getCategory());
        $this->assertSame('others', $testMerchant->getCategory2());
    }

    /**
     * blacklist activation flow should not be allowed to submit full activation form
     */
    public function testUnsupportedActivationFlow()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail', [
            MerchantDetails::ACTIVATION_FLOW => ActivationFlow::BLACKLIST
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

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

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

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

        $this->ba->adminProxyAuth($merchant->getId());

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

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', $merchantId);
        $this->assertEquals($merchant->getWebsite(), 'https://example.com');
        $this->assertEquals($merchant->getName(), 'facebook');

        $merchantDetails = $this->getDbEntityById('merchant_detail', $merchantId);
        $this->assertEquals($merchantDetails->getWebsite(), 'https://example.com');
        $this->assertEquals($merchantDetails->getBusinessName(), 'facebook');
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

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail[MerchantDetails::MERCHANT_ID]);

        $this->updateUploadDocumentData(__FUNCTION__, $documentType);

        $this->startTest();

        $merchantDetail = $this->getDbEntityById(Constants\Entity::MERCHANT_DETAIL, $merchantId);

        $this->assertNotEquals($merchantDetail->getAttribute($documentType), $fileStoreId);

        $document = $this->getDbEntity(Constants\Entity::MERCHANT_DOCUMENT, [MerchantDocuments::FILE_STORE_ID => $fileStoreId]);

        $this->assertNULL($document);

        $document = $this->getDbEntity(Constants\Entity::MERCHANT_DOCUMENT, [MerchantDocuments::FILE_STORE_ID => $merchantDetail->getAttribute($documentType)]);

        $this->assertNotNull($document);
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
}
