<?php

namespace Functional\Merchant\Products;

use Mail;
use Event;
use Mockery;
use RZP\Base\Repository;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Product\Config\DefaultConfigurationHelper;
use RZP\Models\User\Role;
use RZP\Models\Feature\Core;
use RZP\Models\Feature\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Product;
use RZP\Models\Merchant\Methods;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\Product\Metric;
use RZP\Constants\Entity as EntityConstants;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;

class PaymentGatewayConfigTest extends OAuthTestCase
{
    use MocksSplitz;
    use PartnerTrait;
    use WebhookTrait;
    use TestsMetrics;
    use TerminalTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use CreateLegalDocumentsTrait;


    const RZP_ORG = '100000razorpay';

    protected $terminalsServiceMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentGatewayConfigTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->fixtures->connection('test')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.razorpay.com/terms/'], 'business_unit' => 'payments']);
        $this->fixtures->connection('live')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.razorpay.com/terms/'], 'business_unit' => 'payments']);

        $this->mockStorkService();

        $this->app['stork_service']->shouldReceive('sendWhatsappMessage')->andReturn([]);
    }

    public function testCreateDefaultPaymentGatewayConfig()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

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

    public function testCreateProductConfigWithExperimentOnSetMethods()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setUpPartnerWithKycHandled();

        $this->mockSplitzEvaluation();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateProductConfigInvalidInput()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateProductConfigInvalidInput'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products' ;

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

        $this->storkMock->shouldReceive('optInStatusForWhatsapp')->once();

        $this->runRequestResponseFlow($testData);

        $this->assertTrue($metricCaptured);
    }

    /**
     *  Test Default opt in whatsapp status when stork throws an exception
     */
    public function testDefaultOptInStatusForWhatsappInCaseOfException()
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

        $this->storkMock->shouldReceive('optInStatusForWhatsapp')->once()->andThrow(new \Exception('record_not_found') );

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

        $this->storkMock->shouldReceive('optOutForWhatsapp')->once();

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testUpdatePaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $metricCaptured = false;

        $expectedMetricData = $this->getMerchantProductMetricData('payment_gateway');

        $this->mockAndCaptureCountMetric(Metric::PRODUCT_CONFIG_UPDATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        // twice, since runRequestResponseFlow is called twice, and mockery is not closed between those
        $this->storkMock->shouldReceive('optInForWhatsapp')->twice();

        $this->runRequestResponseFlow($testData);

        //This helps in validating graceful handling of flash_checkout feature.
        $response = $this->runRequestResponseFlow($testData);

        $logo_url = $response['active_configuration']['checkout']['logo'];

        // check if logo is fetched from logo_url and stored in /logos path
        $this->assertTrue((bool) preg_match('~\/logos\/[a-zA-Z0-9]{14}.(jpg|png|jpeg)~', $logo_url));

        $this->assertTrue($metricCaptured);
    }

    public function testUpdatePaymentGatewayConfigWithCardsInstrument()
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

        $testData = $this->testData['testUpdatePaymentGatewayConfigWithCardsInstrument'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdatePaymentGatewayConfigOfCardsWithExperimentEnabled()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponseForConsequentRequests(
            [],
            $this->paymentMethodsConfigTerminalResponse("pg.cards.domestic.visa"));

        $this->mockRazorxTreatment();

        $testData = $this->testData['testUpdatePaymentGatewayConfigOfCardsWithExperimentEnabled'];

        $testData['request']['url'] = $this->setPaymentMethodConfigWithExperiment();

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdatePaymentGatewayConfigOfNetbankingWithExperimentEnabled()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponseForConsequentRequests(
            [],
            $this->paymentMethodsConfigTerminalResponse("pg.netbanking.retail.scbl"));

        $this->mockRazorxTreatment();

        $testData = $this->testData['testUpdatePaymentGatewayConfigOfNetbankingWithExperimentEnabled'];

        $testData['request']['url'] = $this->setPaymentMethodConfigWithExperiment();

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdatePaymentGatewayConfigOfWalletWithExperimentEnabled()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponseForConsequentRequests(
            [],
            $this->paymentMethodsConfigTerminalResponse("pg.wallet.airtelmoney"));

        $this->mockRazorxTreatment();

        $testData = $this->testData['testUpdatePaymentGatewayConfigOfWalletWithExperimentEnabled'];

        $testData['request']['url'] = $this->setPaymentMethodConfigWithExperiment();

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdatePaymentGatewayConfigOfPaylaterWithExperimentEnabled()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponseForConsequentRequests(
            [],
            $this->paymentMethodsConfigTerminalResponse("pg.paylater.epaylater"));

        $this->mockRazorxTreatment();

        $testData = $this->testData['testUpdatePaymentGatewayConfigOfPaylaterWithExperimentEnabled'];

        $testData['request']['url'] = $this->setPaymentMethodConfigWithExperiment();

        $this->runRequestResponseFlow($testData);
    }

    private function setPaymentMethodConfigWithExperiment()
    {
        $this->mockRazorxTreatment();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        return '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;
    }

    public function testUpdatePaymentGatewayConfigWithInvalidLogoResolution()
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

        $this->assertTrue($metricCaptured);
    }

    public function testUpdatePaymentGatewayConfigWithInvalidLogoPath()
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

    protected function createAndFetchMocks()
    {
        Mail::fake();
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function($mid, $feature, $mode) {
                                  if ($feature === RazorxTreatment::INSTANT_ACTIVATION_FUNCTIONALITY)
                                  {
                                      return 'on';
                                  }

                                  return 'off';
                              }));
    }

    /**
     * This testcase validates the following
     * 1. Create an registered account through V2 API
     * 2. Create stakeholder for the account
     * 3. Set pricing plan for merchant
     * 4. Create default payment gateway configuration
     * 5. Fetch and verify the requirements for registered account
     * 6. Fetch and verify activation_status in db
     */
    public function testUpdateRegisteredMerchantProductsStatusIfApplicable()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->createAndFetchMocks();

        $featureParams = [
            Entity::ENTITY_ID   => 'DefaultPartner',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'instant_activation_v2_api',
        ];

        (new Core())->create($featureParams, true);

        $this->mockTerminalServiceResponse();

        $this->testData['createRegisteredBusinessTypeAccount']['request']['content']['legal_info'] =  [
            'pan'   =>  'AAACL1234C',
            'cin'   =>  'U65999KA2018PTC114468'
        ];
        $this->testData['createRegisteredBusinessTypeAccount']['response']['content']['legal_info'] =  [
            'pan'   =>  'AAACL1234C',
            'cin'   =>  'U65999KA2018PTC114468'
        ];

        $testData = $this->testData['createRegisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $merchantId = $accountId;

        Account\Entity::verifyIdAndStripSign($merchantId);

        $merchant = (new Merchant\Repository())->findByPublicId($merchantId);

        // set pricing plan here because here is no partner linked for fetching default pricing plan

        $merchant->setPricingPlan('1hDYlICobzOCYt');

        (new Merchant\Repository())->saveOrFail($merchant);

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testRequirementsRegisteredInstantlyActivated'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        Product\Entity::verifyIdAndSilentlyStripSign($merchantProductId);

        $merchantProduct = $this->getDbEntity('merchant_product',  ['id' => $merchantProductId]);

        $this->assertTrue(($merchantProduct[Product\Entity::ACTIVATION_STATUS] === 'instantly_activated'));
    }

    /**
     * This testcase validates the following
     * 1. Create an registered account through V2 API
     * 2. Create stakeholder for the account (without PAN number)
     * 3. Set pricing plan for merchant
     * 4. Create default payment gateway configuration
     * 5. Fetch and verify activation_status in db to be NC
     * 6. Submit the Stakeholder PAN details.
     * 7. Fetch and verify the requirements
     * 8. Fetch and verify activation_status in db to be Instantly_activated
     */

    public function testUpdateRegisteredMerchantProductsStatusByPatchStakeHolder()
    {
        Mail::fake();

        $this->createAndFetchMocks();

        $this->setupPrivateAuthForPartner();

        $featureParams = [
            Entity::ENTITY_ID   => 'DefaultPartner',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'instant_activation_v2_api',
        ];

        (new Core())->create($featureParams, true);

        $this->mockTerminalServiceResponse();

        $this->testData['createRegisteredBusinessTypeAccount']['request']['content']['legal_info'] =  [
            'pan'   =>  'AAACL1234C',
            'cin'   =>  'U65999KA2018PTC114468'
        ];
        $this->testData['createRegisteredBusinessTypeAccount']['response']['content']['legal_info'] =  [
            'pan'   =>  'AAACL1234C',
            'cin'   =>  'U65999KA2018PTC114468'
        ];

        $testData = $this->testData['createRegisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $merchantId = $accountId;

        Account\Entity::verifyIdAndStripSign($merchantId);

        $merchant = (new Merchant\Repository())->findByPublicId($merchantId);

        // set pricing plan here because here is no partner linked for fetching default pricing plan

        $merchant->setPricingPlan('1hDYlICobzOCYt');

        (new Merchant\Repository())->saveOrFail($merchant);

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $productId = $merchantProductId;

        Product\Entity::verifyIdAndSilentlyStripSign($productId);

        $merchantProduct = $this->getDbEntity('merchant_product',  ['id' => $productId]);

        $this->assertTrue(($merchantProduct[Product\Entity::ACTIVATION_STATUS] === 'needs_clarification'));

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $merchantProduct = $this->getDbEntity('merchant_product',  ['id' => $productId]);

        $this->assertTrue(($merchantProduct[Product\Entity::ACTIVATION_STATUS] === 'instantly_activated'));

        $testData = $this->testData['testRequirementsRegisteredInstantlyActivated'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);
    }

    /**
     * This testcase validates the following
     * 1. Create an unregistered account through V2 API
     * 2. Create stakeholder for the account
     * 3. Set poi verification status and pricing plan for merchant
     * 4. Create default payment gateway configuration
     * 5. Fetch and verify the requirements for unregistered account
     * 6. Fetch and verify activation_status in db
     */
    public function testUpdateUnregisteredMerchantProductsStatusIfApplicable()
    {
        Mail::fake();

        $this->createAndFetchMocks();

        $this->setupPrivateAuthForPartner();

        $featureParams = [
            Entity::ENTITY_ID   => 'DefaultPartner',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'instant_activation_v2_api',
        ];

        (new Core())->create($featureParams, true);

        $this->mockTerminalServiceResponse();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $merchantId = $accountId;

        Account\Entity::verifyIdAndStripSign($merchantId);

        $merchant = (new Merchant\Repository())->findByPublicId($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        // set poi verification status here because bvs will verify asynchronously

        $merchantDetails->setPoiVerificationStatus('verified');

        // set pricing plan here because here is no partner linked for fetching default pricing plan

        $merchant->setPricingPlan('1hDYlICobzOCYt');

        (new Merchant\Repository())->saveOrFail($merchant);

        (new Merchant\Detail\Repository())->saveOrFail($merchantDetails);

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testRequirementsUnRegisteredInstantlyActivated'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        Product\Entity::verifyIdAndSilentlyStripSign($merchantProductId);

        $merchantProduct = $this->getDbEntity('merchant_product',  ['id' => $merchantProductId]);

        $this->assertTrue(($merchantProduct[Product\Entity::ACTIVATION_STATUS] === 'instantly_activated'));
    }

    public function testProductConfigRequirementsLimitBreachedWarning()
    {
        $this->testUpdateRegisteredMerchantProductsStatusIfApplicable();

        $merchant = $this->getDbLastEntity('merchant');

        $merchantId = $merchant->getId();

        $this->fixtures->create('merchant_onboarding_escalations',[
            'merchant_id'   => $merchantId,
            'milestone'     => 'hard_limit_ia_v2',
            'threshold'     => '1500000'
        ]);

        $merchantProduct = $this->getDbEntity('merchant_product',  ['merchant_id' => $merchantId]);

        $testData = $this->testData['testRequirementsUnRegisteredInstantlyActivatedLimitBreached'];

        $testData['request']['url'] = '/v2/accounts/acc_' . $merchantId . '/products/acc_prd_' . $merchantProduct['id'];

        $this->runRequestResponseFlow($testData);
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

                return new \WpOrg\Requests\Response();
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

    public function testAcceptTncUsingPostProductConfig()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['acceptTncUsingPostProductConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $productResponse = $this->runRequestResponseFlow($testData);

        $merchantProductId = $productResponse['id'];

        $testData = $this->testData['acceptedTncResponseUsingPatchProductConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testAcceptedAccountTnc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';

        $this->runRequestResponseFlow($testData);
    }

    public function testAcceptTncUsingPatchProductConfig()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

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

    public function testAcceptTncUsingCreateProductConfigForNoDoc()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $testData['request']['content']['no_doc_onboarding'] = true;

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['acceptTncUsingCreateProductConfigForNoDoc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->runRequestResponseFlow($testData);

        $clientIp = $this->getDbEntity('merchant_tnc_acceptance')->pluck('client_ip')->toArray();

        $this->assertEquals($testData['request']['content']['ip'], $clientIp[0]);
    }

    public function testAcceptTncUsingUpdateProductConfigForNoDoc()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $testData['request']['content']['no_doc_onboarding'] = true;

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['createProductConfigForNoDoc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['acceptTncUsingUpdateProductConfigForNoDoc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $clientIp = $this->getDbEntity('merchant_tnc_acceptance')->pluck('client_ip')->toArray();

        $this->assertEquals($testData['request']['content']['ip'], $clientIp[0]);
    }

    public function testAcceptTncWithoutIpForNoDoc()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $testData['request']['content']['no_doc_onboarding'] = true;

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

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


    public function testDefaultBrandColorForAMerchant()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $metricsMock = $this->createMetricsMock();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createAccountWithoutBrandColor'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentConfig'];

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

    public function testOtpVerificationLogInRequirementsArray()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $testData['request']['content']['no_doc_onboarding'] = true;

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['createProductConfigForNoDoc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testRequirementsForOtpVerficationlog'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $contactMobile = $this->getDbEntity('merchant_otp_verification_logs')->pluck('contact_mobile')->toArray();

        $this->assertEquals($testData['request']['content']['otp']['contact_mobile'], $contactMobile[0]);
    }

    //This test need to be updated later, since passing acc number as Integer as input should be restricted
    public function testNonStringAccNumberAsInputWithPatchProductConfig()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponse();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->storkMock->shouldReceive('optOutForWhatsapp')->once();

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testNonStringAccNumberAsInputWithPatchProductConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);
    }

    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn($returnValue);
    }

    private function mockTerminalServiceResponseForConsequentRequests(array $response1, array $response2)
    {
        $response1 = json_decode(json_encode($response1), true);

        $response2 = json_decode(json_encode($response2), true);

        $this->terminalsServiceMock->shouldReceive('proxyTerminalService')
                                   ->andReturn($response1, $response2);
    }

    private function paymentMethodsConfigTerminalResponse(string $instrument)
    {
        return [
            "merchant_instrument_request_id" => "",
            "merchant_id" => "H9sTmdNiFOOFCC",
            "instrument" => $instrument,
            "status" => "requested",
            "comment" => "",
            "created_at" => 0,
            "updated_at" => 0,
            "special_pricing" => "",
            "tags" => null
        ];

        //$data =  '{
        //        "merchant_instrument_request_id": "",
        //        "merchant_id": "H9sTmdNiFOOFCC",
        //        "instrument": \'{instrument}\',
        //        "status": "requested",
        //        "comment": "",
        //        "created_at": 0,
        //        "updated_at": 0,
        //        "special_pricing": "",
        //        "tags": null
        //    }';
        //$data = strstr($data, ['{instrument}' => $instrument]);
        //
        //return $data;
    }

    private function mockSplitzExperiment($experimentId, $id, $variant)
    {
        $input = [
            "experiment_id" => $experimentId,
            "id"            => $id
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => $variant,
                ]
            ]
        ];

        $this->splitzMock = Mockery::mock(SplitzService::class)->makePartial();

        $this->app->instance('splitzService', $this->splitzMock);

        $this->splitzMock
            ->shouldReceive('evaluateRequest')
            ->with($input)
            ->byDefault()
            ->andReturn($output);
    }

    private function mockSplitzEvaluation()
    {
        $input = [
            "experiment_id" => "L3crKVAmTMJ50f",
            "id"            => "10000000000000"
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "JRWRysOmXFWZ9C",
            "id"            => "10000000000000",
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "KJfPdCoug8vfap",
            "id"            => "10000000000000"
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "JIRYzx7YtMuB18",
            "id"            => "10000000000000",
            'request_data'  => json_encode(
                [
                    'id' => "10000000000000",
                ]),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => "enabled"
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "KIYvRvxbpMy7r1",
            "id" => '10000000000000',
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable'
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
    }

    private  function createNoDocOnboardingAccount(array & $accountCreationPayload)
    {
        $accountCreationPayload['no_doc_onboarding'] = true;
    }

    public function testUpdatePaymentGatewayConfigBySubmitingOtpPayload()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['createUnregisteredBusinessTypeAccountForNoDocWithPan'];

        $testData['request']['content']['no_doc_onboarding'] = true;

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['productConfigCreateForNoDocWithTnc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->storkMock->shouldReceive('optOutForWhatsapp')->once();

        $response = $this->runRequestResponseFlow($testData);


        $merchantProductId = $response['id'];

        $testData = $this->testData['testUpdatePaymentGatewayConfigForNoDoc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testEmptyRequirements'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        Stakeholder\Entity::verifyIdAndSilentlyStripSign($stakeholderId);

        $stakeholder = $this->getDbEntity('stakeholder',  ['id' => $stakeholderId]);

        $this->assertTrue(($stakeholder[Stakeholder\Entity::AADHAAR_LINKED] === 0));
    }

    public function testNoOptionalRequirementArrivesForPartiallyActivatedNoDocMerchant()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setUpPartnerWithKycHandled();

        $featureParams = [
            Entity::ENTITY_ID   => '10000000000000',
            Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Entity::NAME        => 'subm_no_doc_onboarding',
        ];

        (new Core())->create($featureParams, true);

        $testData = $this->testData['createUnregisteredBusinessTypeAccountForNoDocWithPan'];

        $testData['request']['content']['no_doc_onboarding'] = true;

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['productConfigCreateForNoDocWithTnc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->storkMock->shouldReceive('optOutForWhatsapp')->once();

        $response = $this->runRequestResponseFlow($testData);


        $merchantProductId = $response['id'];

        $testData = $this->testData['testUpdatePaymentGatewayConfigForNoDoc'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testEmptyRequirements'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        $merchant = $this->getDbEntity('merchant', ['id' => $accountId]);

        $input = [
            'activation_status' => Detail\Status::ACTIVATED_KYC_PENDING
        ];
        (new Detail\Core)->updateActivationStatus($merchant, $input, $merchant);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');
        $this->assertEquals(Detail\Status::ACTIVATED_KYC_PENDING, $merchantDetail->getActivationStatus());

        $testData = $this->testData['testEmptyRequirements'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;
    }

    public function testConfigRequirementsAndCreateLegalDocsForMerchantConsentOnSubmitActivationWithIpExpDisabled()
    {
        $bvsMock = $this->mockCreateLegalDocument();

        $bvsMock->expects($this->once())->method('createLegalDocument')->withAnyParameters();

        $merchantId = $this->createLegalDocsForMerchantConsentOnSubmitActivation(false, false, false);

        $merchantConsents = $this->getDbEntities('merchant_consents',  ['merchant_id' => $merchantId], Mode::LIVE)->toArray();

        $this->assertCount(1, $merchantConsents);
    }

    public function testConfigRequirementsAndCreateLegalDocsForMerchantConsentOnSubmitActivationWithIpExpEnabled()
    {
        $bvsMock = $this->mockCreateLegalDocument();

        $bvsMock->expects($this->never())->method('createLegalDocument')->withAnyParameters();

        $merchantId = $this->createLegalDocsForMerchantConsentOnSubmitActivation(false, false, true);

        $merchantConsents = $this->getDbEntities('merchant_consents',  ['merchant_id' => $merchantId], Mode::LIVE)->toArray();

        $this->assertCount(0, $merchantConsents);
    }

    public function testCreateLegalDocsForMerchantConsentOnSubmitActivationForNoDocMerchant()
    {
        $bvsMock = $this->mockCreateLegalDocument();

        $bvsMock->expects($this->once())->method('createLegalDocument')->withAnyParameters();

        $merchantId = $this->createLegalDocsForMerchantConsentOnSubmitActivation(true, false, false);

        $merchantConsents = $this->getDbEntities('merchant_consents',  ['merchant_id' => $merchantId], Mode::LIVE)->toArray();

        $this->assertCount(1, $merchantConsents);
    }

    public function testCreateLegalDocsForMerchantConsentOnSubmitActivationForNoDocMerchantConsentsAlreadyPresent()
    {
        $bvsMock = $this->mockCreateLegalDocument();

        $bvsMock->expects($this->never())->method('createLegalDocument')->withAnyParameters();

        $this->createLegalDocsForMerchantConsentOnSubmitActivation(true, true, false);
    }

    protected function createLegalDocsForMerchantConsentOnSubmitActivation(bool $isSubmerchantNoDocEnabled, bool $isConsentAlreadyPresent, bool $isPassingIpExpEnabled)
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $this->mockTerminalServiceResponse();

        if($isPassingIpExpEnabled === true)
        {
            $this->mockSplitzExperiment('L3crKVAmTMJ50f', 'DefaultPartner', 'enable');
        }
        else
        {
            $this->mockSplitzExperiment('L3crKVAmTMJ50f', 'DefaultPartner','disable');
        }

        $testData = $this->testData['createRegisteredBusinessTypeAccount'];

        if($isSubmerchantNoDocEnabled === true)
        {
            $testData = $this->testData['createUnregisteredBusinessTypeAccountForNoDocWithPan'];

            $featureParams = [
                Entity::ENTITY_ID   => 'DefaultPartner',
                Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
                Entity::NAME        => 'subm_no_doc_onboarding',
            ];

            (new Core())->create($featureParams, true);

            $testData['request']['content']['no_doc_onboarding'] = true;
        }

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $merchantId = $accountId;

        Account\Entity::verifyIdAndSilentlyStripSign($merchantId);

        if($isConsentAlreadyPresent === true)
        {
            $this->fixtures->create('merchant_consents',
                [
                    'merchant_id' => $merchantId
                ]);
        }

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        if($isSubmerchantNoDocEnabled === true)
        {
            $testData = $this->testData['productConfigCreateForNoDocWithTnc'];
        }

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testUpdateSettlementDetailsForRegisteredBusiness'];

        if($isPassingIpExpEnabled === true)
        {
            $testData = $this->testData['testUpdateSettlementDetailsForRegisteredBusinessWithoutIp'];
        }

        if($isSubmerchantNoDocEnabled === true)
        {
            $testData = $this->testData['testUpdatePaymentGatewayConfigForNoDoc'];
        }

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        if($isSubmerchantNoDocEnabled === false)
        {
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

            if($isPassingIpExpEnabled === true)
            {
                $testData = $this->testData['testAcceptAccountTncWithoutIpWhenExperimentEnabled'];
            }

            $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';

            $this->runRequestResponseFlow($testData);

            $testData = $this->testData['testEmptyRequirements'];

            $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

            $this->runRequestResponseFlow($testData);
        }

        return $merchantId;
    }

    public function testAcceptAccountTncWithoutIpWhenExperimentDisabled()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setupPrivateAuthForPartner();

        $this->mockSplitzExperiment('L3crKVAmTMJ50f', 'DefaultPartner', 'disable');

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/tnc';

        $this->runRequestResponseFlow($testData);
    }

    public function testAcceptTncWithoutIpUsingPostProductConfigWhenExperimentDisabled()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setupPrivateAuthForPartner();

        $this->mockSplitzExperiment('L3crKVAmTMJ50f', 'DefaultPartner', 'disable');

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->runRequestResponseFlow($testData);
    }

    public function testAcceptTncWithoutIpUsingPatchProductConfigWhenExperimentDisabled()
    {
        Mail::fake();

        $this->mockTerminalServiceResponse();

        $this->setupPrivateAuthForPartner();

        $this->mockSplitzExperiment('L3crKVAmTMJ50f', 'DefaultPartner', 'disable');

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);
    }
}

