<?php

namespace Functional\Merchant\Products;

use Mail;
use Event;
use RZP\Constants\Mode;
use RZP\Models\User\Role;
use RZP\Models\Merchant\Methods;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Product\Metric;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class PaymentGatewayConfigTest extends OAuthTestCase
{
    use PartnerTrait;
    use WebhookTrait;
    use TestsMetrics;
    use TerminalTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;


    const RZP_ORG = '100000razorpay';

    protected $terminalsServiceMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentGatewayConfigTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->fixtures->connection('test')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.terms.com'], 'business_unit' => 'payments']);
        $this->fixtures->connection('live')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.terms.com'], 'business_unit' => 'payments']);

        $this->mockStorkService();
    }

    public function testCreateDefaultPaymentGatewayConfig()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $metricsMock = $this->createMetricsMock();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $metricCaptured = false;

        $expectedMetricData = $this->getMerchantProductMetricData('payment_gateway');

        $this->mockAndCaptureCountMetric(Metric::PRODUCT_CONFIG_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $this->runRequestResponseFlow($testData);

        $this->assertTrue($metricCaptured);

        $merchantProduct = $this->getDbLastEntity('merchant_product');

        $merchantProductRequest = $this->getDbLastEntity('merchant_product_request');

        $this->validateMerchantProductRequest($merchantProduct, $merchantProductRequest);

        $merchant = $this->getDbEntity('merchant', ['id' => $merchantProduct->getMerchantId()]);

        // The below function call is idempotent
        (new Methods\Core())->setDefaultMethods($merchant);
    }

    public function testCreateProductConfigInvalidInput()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateProductConfigInvalidInput'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchDefaultPaymentGatewayConfig()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setupPrivateAuthForPartner();

        $metricsMock = $this->createMetricsMock();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testFetchDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $metricCaptured = false;

        $expectedMetricData = $this->getMerchantProductMetricData('payment_gateway');

        $this->mockAndCaptureCountMetric(Metric::PRODUCT_CONFIG_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $this->runRequestResponseFlow($testData);

        $this->assertTrue($metricCaptured);
    }

    public function testUpdatePaymentGatewayConfig()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponse();

        $metricsMock = $this->createMetricsMock();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testUpdatePaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $metricCaptured = false;

        $expectedMetricData = $this->getMerchantProductMetricData('payment_gateway');

        $this->mockAndCaptureCountMetric(Metric::PRODUCT_CONFIG_UPDATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $this->runRequestResponseFlow($testData);

        //This helps in validating graceful handling of flash_checkout feature.
        $this->runRequestResponseFlow($testData);

        $this->assertTrue($metricCaptured);
    }

    /**
     * This testcase validates the following
     * 1. Create an unregistered account through V2 API
     * 2. Create default payment gateway configuration
     * 3. Fetch the requirements for unregistered account
     * 4. Update settlement details
     * 5. Fetch and verify settlements requirements are not shown in requirements
     * 6. Create stakeholder for the account
     * 7. Update POI details for stakeholder
     * 8. Fetch and verify POI field requirements are not shown in requirements
     * 9. Upload POA documents for stakeholders
     * 10. Fetch and verify POA document requirements are not shown in requirements
     */
    public function testRequirementsForUnregisteredBusiness()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponse();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testRequirementsForUnregisteredBusiness'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['updateSettlementFields'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testRequirementsForUnregisteredBusinessAfterStakeholderDetailsSubmission'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharFront');

        $testData = $this->testData['testPostStakeholderDocumentAadharFront'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharBack');

        $testData = $this->testData['testPostStakeholderDocumentAadharBack'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['acceptAccountTnc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testEmptyRequirements'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        Stakeholder\Entity::verifyIdAndSilentlyStripSign($stakeholderId);

        $stakeholder = $this->getDbEntity('stakeholder',  ['id' => $stakeholderId]);

        $this->assertTrue(($stakeholder[Stakeholder\Entity::AADHAAR_LINKED] === 0));
    }

    /**
     * This testcase validates the following
     * 1. Create an registered account through V2 API
     * 2. Create default payment gateway configuration
     * 3. Fetch the requirements for registered account
     * 4. Update settlement details
     * 5. Fetch and verify settlements requirements are not shown in requirements
     * 6. Create stakeholder for the account
     * 7. Update POI details for stakeholder
     * 8. Fetch and verify POI field requirements are not shown in requirements
     * 9. Upload POA documents for stakeholders
     * 10. Fetch and verify POA document requirements are not shown in requirements
     * 11. Update business identification fields for registered business
     * 12. Upload business identification documents for registered business
     * 13. Fetch and verify business identification requirements are not shown in requirements
     */
    public function testRequirementsForRegisteredBusiness()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponse();

        $testData = $this->testData['createRegisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testRequirementsForRegisteredBusiness'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['updateSettlementFieldsForRegisteredBusiness'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testRequirementsForRegisteredBusinessAfterStakeholderDetailsSubmission'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharFront');

        $testData = $this->testData['testPostStakeholderDocumentAadharFront'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharBack');

        $testData = $this->testData['testPostStakeholderDocumentAadharBack'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['updateBusinessProofDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId;

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostBusinessProofDocument');

        $testData = $this->testData['testPostBusinessProofDocument'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/documents';

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostBusinessPanDocument');

        $testData = $this->testData['testPostBusinessPanDocument'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/documents';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['acceptAccountTnc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testEmptyRequirements'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

    }

    /**
     * The following testcase does the following
     * 1. Create a unregistered account
     * 2. Create default payment gateway config
     * 3. Create a stakeholder
     * 4. Mark few fields as NC by fixtures
     * 5. Verify only NC marked fields/ documents are appearing in requirements
     * 6. Validate validation error if extra fields / documents are passed other than NC fields / documents
     * 7. Update a valid NC field.
     * 8. Validate latest kyc clarificaiton reason for that NC field has been updated with `acknowledged = true`
     */
    public function testNeedsClarificationForUnregisteredBusiness()
    {
        Mail::fake();

        $key = $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponse();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $merchantId = substr($accountId, 4);

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        // This is not the exact flow via APIs, mocking the flow with fixtures
        $this->updateKycClarificationsAndMarkMerchantAsNC($merchantId, 'live');

        $this->updateKycClarificationsAndMarkMerchantAsNC($merchantId, 'test');

        $testData = $this->testData['testRequirementsInNCState'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->ba->privateAuth($key);

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateAccountNonNCFields'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId;

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testUploadNonNCDocument');

        $testData = $this->testData['testUploadNonNCDocument'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['content'] = ['name' => 'abcd'];

        $testData['response']['content'] = ['name' => 'abcd'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $this->verifyKycClarificationReasonAcknowledged('promoter_pan_name' , $merchantId, 'clarification_reasons');

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharFront');

        $testData = $this->testData['testPostStakeholderDocumentAadharFront'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $testData['response']['content'] = [];

        $this->runRequestResponseFlow($testData);

        $this->verifyKycClarificationReasonAcknowledged('aadhar_front' , $merchantId, 'clarification_reasons');

        $testData = $this->testData['testUpdatePaymentGatewayConfig'];

        $testData['request']['content'] = ['settlements' => ['account_number' => '123456780']];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $testData['response']['content'] = ['active_configuration' => ['settlements' => ['account_number' => '123456780']]];

        $this->runRequestResponseFlow($testData);

        $this->verifyKycClarificationReasonAcknowledged('bank_account_number' , $merchantId, 'additional_details');
    }

    private function verifyKycClarificationReasonAcknowledged(string $field, string $merchantId, string $updateKey)
    {
        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $kycClarificationReasons = $merchantDetails->getKycClarificationReasons();

        $clarificationReasons = $kycClarificationReasons[$updateKey];

        $this->assertTrue($clarificationReasons[$field][0]['acknowledged']);
    }

    private function updateKycClarificationsAndMarkMerchantAsNC(string $merchantId, string $mode)
    {
        $this->fixtures->on($mode)->edit('merchant_detail', $merchantId, [
            'kyc_clarification_reasons' => [
                'clarification_reasons' => [
                    'aadhar_front'      => [[
                                                'reason_type' => 'predefined',
                                                'field_value' => 'adnakdad',
                                                'reason_code' => 'illegible_doc',
                                                'is_current'  => true,
                                                'from'        => 'admin'
                                            ],
                    ],
                    'promoter_pan_name' => [[
                                                'reason_type' => 'predefined',
                                                'field_value' => 'adnakdad',
                                                'reason_code' => 'signatory_name_not_matched',
                                                'is_current'  => true,
                                                'from'        => 'admin'
                                            ],
                    ],
                ],
                'additional_details' => [
                    'bank_account_number' => [[
                                                  'reason_type' => 'predefined',
                                                  'field_value' => '1234567890',
                                                  'reason_code' => 'unable_to_validate_acc_number',
                                                  'from'        => 'system'
                                              ]]
                ]
            ],
            'activation_status'         => 'needs_clarification',
            'submitted'                 => 1,
            'locked'                    => 0,
            'bank_account_number'       => '1234567890'
        ]);
    }

    public function testPartnerProductStatusEvent()
    {
        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createRegisteredBusinessTypeAccount'];

        $this->mockTerminalServiceResponse();

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $merchantId = substr($accountId, 4);

        $this->fixtures->on('live')->edit('merchant_detail', $merchantId, ['activation_status' => 'under_review']);
        $this->fixtures->on('test')->edit('merchant_detail', $merchantId, ['activation_status' => 'under_review']);

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/acc_' . $merchantId . '/products';

        $merchantProductResponse = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testMerchantActivationStatus'];

        $testData['request']['url'] = "/merchant/activation/$merchantId/activation_status";

        $this->ba->adminAuth();

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($merchantProductResponse, $merchantId) {
                $this->validateStorkWebhookFireEvent($merchantProductResponse, $payload, $merchantId);

                return new \Requests_Response();
            });

        $this->runRequestResponseFlow($testData);

    }

    public function testDefaultPaymentMethods()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testDefaultPaymentMethods'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->runRequestResponseFlow($testData);
    }

    public function testTncAcceptance()
    {
        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData                   = $this->testData['fetchAccountTnc'];
        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';
        $this->runRequestResponseFlow($testData);

        $testData                   = $this->testData['acceptAccountTnc'];
        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';
        $this->runRequestResponseFlow($testData);
    }

    protected function acceptTncUsingPostProductConfig()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['acceptTncUsingPostProductConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testAcceptedAccountTnc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';

        $this->runRequestResponseFlow($testData);
    }

    protected function acceptTncUsingPatchProductConfig()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['acceptTncUsingPatchProductConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testAcceptedAccountTnc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';

        $this->runRequestResponseFlow($testData);
    }

    protected function validateStorkWebhookFireEvent($testData, $storkPayload, $merchantId)
    {
        if ($storkPayload['event']['name'] === 'product.payment_gateway.needs_clarification')
        {
            $this->assertEquals('merchant', $storkPayload['event']['owner_type']);
            $this->assertEquals($merchantId, $storkPayload['event']['owner_id']);
            $merchantProductInPayload = [
                'id'                => $testData['id'],
                'merchant_id'       => 'acc_' . $merchantId,
                'activation_status' => 'needs_clarification'
            ];
            $completePayload          = json_decode($storkPayload['event']['payload'], true);
            $storkActualPayload       = $completePayload['payload'];
            $this->assertArraySelectiveEquals($storkActualPayload['merchant_product']['entity'], $merchantProductInPayload);
        }
    }

    private function validateMerchantProductRequest($merchantProduct, $merchantProductRequest, $partnerId = 'DefaultPartner')
    {
        $expected = [
            'requested_entity_id' => $partnerId,
            'merchant_product_id' => $merchantProduct->getId(),
            'id'                  => $merchantProductRequest->getId()];

        $this->assertArraySelectiveEquals($expected, $merchantProductRequest->toArrayPublic());
    }

    protected function setupPrivateAuthForPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->fixtures->user->createUserForMerchant($partner->getId(), [], Role::OWNER, Mode::LIVE);

        $orgHostName = $this->fixtures->org->build('org_hostname', [
            'org_id'   => self::RZP_ORG,
            'hostname' => 'dashboard.razorpay.in'
        ]);

        $orgHostName->setConnection('live')->saveOrFail();

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        return $key;
    }

    protected function updateUploadDocumentData(string $callee)
    {
        $testData                             = &$this->testData[$callee];
        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../../Storage/k.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../../Storage/k.png'),
            null,
            true);
    }

    private function mockTerminalServiceResponse(): void
    {
        $data = '[
                {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.netbanking.retail.scbl",
                "status": "activated",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.netbanking.retail.aubl",
                "status": "activated",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.netbanking.retail.abpb",
                "status": "requestable",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.netbanking.retail.airp",
                "status": "activated",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.emi.cardless_emi.zestmoney",
                "status": "activated",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.emi.cardless_emi.instacred",
                "status": "requestable",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.emi.cardless_emi.earlysalary",
                "status": "requestable",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.emi.debit",
                "status": "activated",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            },
            {
                "merchant_instrument_request_id": "",
                "merchant_id": "H9sTmdNiFOOFCC",
                "instrument": "pg.emi.credit",
                "status": "activated",
                "comment": "",
                "created_at": 0,
                "updated_at": 0,
                "special_pricing": "",
                "tags": null
            }
        ]';

        $data = json_decode($data, true);

        $this->mockTerminalsServiceProxyRequest($data);
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }

    private function getMerchantProductMetricData(string $productName): array
    {
        return [
            'product' => $productName,
        ];
    }
}

