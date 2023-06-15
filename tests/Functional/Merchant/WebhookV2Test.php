<?php

namespace RZP\Tests\Functional\Merchant;

use Illuminate\Http\UploadedFile;
use RZP\Models\Merchant\Webhook\Event;
use Illuminate\Database\Eloquent\Factory;

use Mail;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Feature;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\WebhookV2\Metric;
use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class WebhookV2Test extends TestCase
{
    use TestsMetrics;
    use PartnerTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookV2Data.php';

        parent::setUp();



        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['payout']);
    }

    /*
     * Partner type reseller, cannot create webhook
     */
    public function testCreateAppWebhookInvalidPartnerType()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'reseller']);

        $this->startTest();
    }

    /*
     * Partner type pure platform, can create webhook
     */
    public function testCreateAppWebhookPurePlatform()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'pure_platform']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testSubscribePurePlatformSpecificWebhook()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'pure_platform']);

        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Not partner yet but tagged OAuth, can create webhook
     */
    public function testCreateAppWebhookOAuthTag()
    {
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Partner type bank, also tagged OAuth, cannot create webhook
     * as this should ideally not happen and we should prevent by default
     */
    public function testCreateAppWebhookBankWithOAuthTag()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'bank']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    /*
     * Partner type fully managed, can create webhook irrespective
     * of the oauth tag
     */
    public function testCreateAppWebhookFullyManagedWithOAuthTag()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);
        $this->addOAuthTag();
        $this->createOAuthApplication(['id' => '10000000000App', 'merchant_id' => '10000000000000']);

        $this->startTest();
    }

    public function testEditWebhookByNonOwnerUser()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => '10000000000000',
            'role'        => 'support',
        ]);

        $this->dontExpectAnyStorkServiceRequest();

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->toArrayPublic());

        $this->startTest();
    }

    public function testGetWebhookEvents()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $response = $this->startTest();

        $this->assertContains('order.paid', $response);
        $this->assertContains('virtual_account.credited', $response);
        $this->assertNotContains('subscription.charged', $response);
        $this->assertNotContains('payment.pending', $response);

        // Events of other products (e.g. banking) should not come in response.
        $this->assertNotContains('transaction.created', $response);

        //Pure-platform partner specific events should not come in response.
        $this->assertNotContains('account.app.authorization_revoked', $response);
    }

    public function testGetWebhookEventsFor1CC()
    {
        $this->fixtures->merchant->addFeatures(['one_click_checkout']);

        $response = $this->startTest();

        $this->assertContains('payment.pending', $response);
    }

    public function testGetWebhookEventsForProductBanking()
    {

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        // This is required, because this is going to on board the merchant on X on the test mode
        // which requires the terminal entity to be present
        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testCreateWebhookForPartner()
    {
        $this->assignMerchantAsPartnerAggregator();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('createWebhookForPartner');

        $this->startTest();
    }

    public function testCreateWebhookForPartnerMerchantNoAppAccessFailure()
    {
        $this->assignMerchantAsPartnerAggregator();

        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testCreateWebhookForOAuth()
    {
        $this->addOAuthTag();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('createWebhookForOAuth');

        $this->startTest();
    }

    public function testCreateWebhookForOAuthFailure()
    {
        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testCreateWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('listWebhookForBankingWhenReturnsNoWebhooks');
        $this->expectStorkServiceRequestForAction('createWebhookForBanking');

        $this->startTest();
    }

    public function testCreateWebhookForBankingWithMFNFeatureEnabled()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->fixtures->merchant->addFeatures([Feature\Constants::MFN]);

        $this->startTest();
    }

    public function testCreateWebhookWithPayoutCreatedEvent()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->startTest();
    }

    public function testUpdateWebhookWithPayoutCreatedEvent()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 4
        ]);
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->startTest();
    }

    public function testCreateWebhookForBankingAlreadyExistsFailure()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('listWebhookForBankingBeforeCreate');
        $this->dontExpectStorkServiceRequest('/twirp/rzp.stork.webhook.v1.WebhookAPI/Create');

        $this->startTest();
    }

    public function testCreateWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('createWebhookForPrimary');

        $this->startTest();
    }

    //event is not valid for the product
    public function testCreateWebhookInvalidProductEventFailure()
    {
        $this->startTest();
    }

    public function testGetWebhookForHosted()
    {
        $this->expectStorkServiceRequestForAction('getWebhookWithSecret');

        $this->ba->hostedProxyAuth();
        $this->startTest();
    }

    public function testGetWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('getWebhookForBanking');

        $this->startTest();
    }

    public function testGetWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('getWebhookForPrimary');

        $this->startTest();
    }

    public function testListWebhookForHosted()
    {
        $this->expectStorkServiceRequestForAction('listWebhookWithSecret');

        $this->ba->hostedProxyAuth();
        $this->startTest();
    }

    public function testListWebhookWithPrivateAuth()
    {
        $this->expectStorkServiceRequestForAction('listWebhookWithPrivateAuth');

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testListWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('listWebhookForBanking');

        $this->startTest();
    }

    public function testListWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('listWebhookForPrimary');

        $this->startTest();
    }

    public function testListWebhookForPartner()
    {
        $this->assignMerchantAsPartnerAggregator();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('listWebhookForPartner');

        $this->startTest();
    }

    public function testListWebhookForPartnerMerchantNotPartnerFailure()
    {
        $this->startTest();
    }

    public function testListWebhookForPartnerMerchantNoAppAccessFailure()
    {
        $this->assignMerchantAsPartnerAggregator();

        $this->startTest();
    }

    public function testUpdateWebhookForBanking()
    {
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 4
        ]);
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->expectStorkServiceRequestForAction('updateWebhookForBanking');

        $this->startTest();
    }

    public function testUpdateWebhookForPrimary()
    {
        $this->expectStorkServiceRequestForAction('updateWebhookForPrimary');

        $this->startTest();
    }

    public function testUpdateWebhookForOAuth()
    {
        $this->addOAuthTag();
        $this->addMerchantApplicationMapping();

        $this->expectStorkServiceRequestForAction('updateWebhookForOAuth');

        $this->startTest();
    }

    public function testUpdateWebhookForOAuthMerchantNotPartnerFailure()
    {
        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testUpdateWebhookForOAuthMerchantNoAppAccessFailure()
    {
        $this->addOAuthTag();

        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    //event is not valid for the product
    public function testUpdateWebhookInvalidProductEventFailure()
    {
        $this->dontExpectAnyStorkServiceRequest();

        $this->startTest();
    }

    public function testSendDisableWebhookEmailForStork()
    {
        $this->ba->storkAppAuth();

        Mail::fake();

        $this->startTest();

        $testData = $this->testData[__FUNCTION__.'Data'];
        // test mail sent
        Mail::assertQueued(WebhookMail::class, function ($mail) use ($testData)
        {
            $this->assertEquals($mail->viewData['url'], $testData['url']);

            $this->assertEquals($mail->viewData['mode'], $testData['mode']);

            $this->assertEquals($mail->viewData['subject'], $testData['subject']);

            return ($mail->hasFrom('alerts@razorpay.com') and ($mail->hasTo($testData['alert_email'])));
        });
    }

    public function testProcessWebhookEventsFromCsv()
    {
        $filepath = __DIR__.'/helpers/webhook_events.csv';
        $file = new UploadedFile($filepath, 'webhook_events.csv', 'text/csv', null, true);
        $this->testData[__FUNCTION__]['request']['files']['file'] = $file;

        $this->expectWebhookEvent('payment.created');
        $this->expectWebhookEvent('payment.failed');
        $this->expectWebhookEvent('payment.captured');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testProcessWebhookEventsFromCsvWhenInvalidPayload()
    {
        $contents = file_get_contents(__DIR__.'/helpers/webhook_events.csv');
        $contents = str_replace('payment.failed', 'unknown.event', $contents);
        $filepath = '/tmp/webhook_events.csv';
        file_put_contents($filepath, $contents);
        $file = new UploadedFile($filepath, 'webhook_events.csv', 'text/csv', null, true);
        $this->testData[__FUNCTION__]['request']['files']['file'] = $file;

        $this->dontExpectAnyWebhookEvent();

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function addOAuthTag(string $merchantId = '10000000000000')
    {
        $merchant = Merchant\Entity::find($merchantId);
        $merchant->reTag(["oauth"]);
        $merchant->saveOrFail();
    }

    protected function assignMerchantAsPartnerAggregator($merchantId = '10000000000000')
    {
        $this->fixtures->merchant->edit($merchantId, ['partner_type' => 'aggregator']);
    }

    protected function addMerchantApplicationMapping(string $appId = '10000000000App', string $merchantId = '10000000000000')
    {
        $this->createOAuthApplication(
            [
                'id'          => $appId,
                'merchant_id' => $merchantId,
            ]
        );
    }

    public function testGetP2pWebhookEvents()
    {
        $this->fixtures->merchant->addFeatures(['p2p_upi']);

        $response = $this->startTest();

        $this->assertContains('customer.transaction.created', $response);
        $this->assertContains('customer.transaction.completed', $response);
        $this->assertContains('customer.transaction.failed', $response);
        $this->assertContains('customer.vpa.created', $response);
        $this->assertContains('customer.vpa.deleted', $response);
        $this->assertContains('customer.verification.completed', $response);
        $this->assertContains('customer.deregistration.completed', $response);
    }

    public function testCreateAndFetchOnboardingWebhook()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $metricsMock = $this->createMetricsMock();
        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $testData = $this->testData['testCreateOnboardingWebhook'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/webhooks';

        $this->expectStorkServiceRequestForAction('createWebhookForOnboarding');

        $metricCaptured = false;

        $expectedMetricData = $this->getOnboardingWebhookMetricDimensions($partner);
        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_WEBHOOK_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        // creating a sub-merchant webhook
        $response = $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        $testData = $this->testData['testGetOnboardingWebhook'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/webhooks/' . $response['id'];

        $this->expectStorkServiceRequestForAction('getWebhookForOnboarding');

        $metricCaptured = false;
        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_WEBHOOK_FETCH_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        // fetching a sub-merchant webhook
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);

        $testData = $this->testData['testListOnboardingWebhook'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/webhooks?skip=0&count=25';

        $this->expectStorkServiceRequestForAction('listWebhookForOnboarding');

        $metricCaptured = false;
        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_WEBHOOK_FETCH_ALL_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        // fetching all sub-merchant webhooks
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);
    }

    public function testUpdateOnboardingWebhook()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $testData = $this->testData['testCreateOnboardingWebhook'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/webhooks';

        $this->expectStorkServiceRequestForAction('createWebhookForOnboarding');

        // creating a sub-merchant webhook
        $response = $this->runRequestResponseFlow($testData);

        $metricsMock = $this->createMetricsMock();
        $metricCaptured = false;
        $expectedMetricData = $this->getOnboardingWebhookMetricDimensions($partner);
        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_WEBHOOK_UPDATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testUpdateOnboardingWebhook'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/webhooks/' . $response['id'];

        $this->expectStorkServiceRequestForAction('updateWebhookForOnboarding');

        // updating a sub-merchant webhook
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);
    }

    public function testDeleteOnboardingWebhook()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $testData = $this->testData['testCreateOnboardingWebhook'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/webhooks';

        $this->expectStorkServiceRequestForAction('createWebhookForOnboarding');

        // creating a sub-merchant webhook
        $response = $this->runRequestResponseFlow($testData);

        $metricsMock = $this->createMetricsMock();
        $metricCaptured = false;
        $expectedMetricData = $this->getOnboardingWebhookMetricDimensions($partner);
        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V2_WEBHOOK_DELETE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $testData = $this->testData['testDeleteOnboardingWebhook'];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() . '/webhooks/' . $response['id'];

        $this->expectStorkServiceRequestForAction('deleteWebhookForOnboarding');

        // deleting a sub-merchant webhook
        $this->runRequestResponseFlow($testData);
        $this->assertTrue($metricCaptured);
    }

    public function testInvalidOnboardingWebhookActionByPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->fixtures->create('merchant', ['id' => 'submerchantXXX']);
        $subMerchantId = 'submerchantXXX';

        $this->ba->privateAuth($key);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchantId .'/webhooks';

        // create a webhook for a sub-merchant unmapped to the partner
        $this->runRequestResponseFlow($testData);
    }

    public function testInvalidOnboardingWebhookInput()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $metricsMock = $this->createMetricsMock();
        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/v2/accounts/acc_'. $subMerchant->getId() .'/webhooks';

        // create a webhook with empty events array
        $this->runRequestResponseFlow($testData);

        $testData['request']['content'] = [];
        $testData['response']['content']['error']['description'] = "input cannot be empty";

        // create a webhook with empty input
        $this->runRequestResponseFlow($testData);
    }

    private function getOnboardingWebhookMetricDimensions($partner)
    {
        return [
            'partner_type' => $partner->getPartnerType()
        ];
    }

    public function testCreateOnboardingWebhookForPureplatform()
    {
        $this->setPurePlatformContext();

        $testData = $this->testData['testCreateOnboardingWebhookForPurePlatform'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/webhooks';

        $this->expectStorkServiceRequestForAction('createWebhookForOnboardingForPurePlatform');

        // creating a sub-merchant webhook
        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testGetOnboardingWebhookForPurePlatform'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID . '/webhooks/' . $response['id'];

        $this->expectStorkServiceRequestForAction('getWebhookForOnboardingForPurePlatform');

        // fetching a sub-merchant webhook
        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testListOnboardingWebhookForPurePlatform'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID . '/webhooks?skip=0&count=25';

        $this->expectStorkServiceRequestForAction('listWebhookForOnboardingForPurePlatform');

        // fetching all sub-merchant webhooks
        $this->runRequestResponseFlow($testData);
    }

    public function testUpdateOnboardingWebhookForPureplatform()
    {
        $this->setPurePlatformContext();

        $testData = $this->testData['testCreateOnboardingWebhookForPurePlatform'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/webhooks';

        $this->expectStorkServiceRequestForAction('createWebhookForOnboardingForPurePlatform');

        // creating a sub-merchant webhook
        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateOnboardingWebhookForPurePlatform'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID . '/webhooks/' . $response['id'];

        $this->expectStorkServiceRequestForAction('updateWebhookForOnboardingForPurePlatform');

        // updating a sub-merchant webhook
        $this->runRequestResponseFlow($testData);
    }

    public function testDeleteOnboardingWebhookForPureplatform()
    {
        $this->setPurePlatformContext();

        $testData = $this->testData['testCreateOnboardingWebhookForPurePlatform'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID .'/webhooks';

        $this->expectStorkServiceRequestForAction('createWebhookForOnboardingForPurePlatform');

        // creating a sub-merchant webhook
        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testDeleteOnboardingWebhookForPurePlatform'];
        $testData['request']['url'] = '/v2/accounts/acc_'. Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID . '/webhooks/' . $response['id'];

        $this->expectStorkServiceRequestForAction('deleteWebhookForOnboardingForPurePlatform');

        // deleting a sub-merchant webhook
        $this->runRequestResponseFlow($testData);
    }

    public function testDeleteWebhookForProductBanking()
    {
        $this->testCreateWebhookForBanking();

        $testData = $this->testData['testDeleteWebhookForProductBanking'];
        $testData['request']['url'] = '/webhooks/' . 'webhook0000001';

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testGetWebhookEventsForAggregatorPartner()
    {
        $this->fixtures->merchant->markPartner('aggregator', '10000000000000');

        $response = $this->startTest();

        //Pure-platform partner specific events should not come in response.
        $this->assertNotContains('account.app.authorization_revoked', $response);
    }

    public function testGetWebhookEventsForPurePlatformPartner()
    {
        $this->fixtures->merchant->markPartner('pure_platform', '10000000000000');

        $response = $this->startTest();

        //Pure-platform partner specific events should arrive in response.
        $this->assertContains('account.app.authorization_revoked', $response);
    }

    public function testGetWebhookEventsWithAccountStatusEventsForPurePlatformPartner()
    {
        $this->fixtures->merchant->markPartner('pure_platform', '10000000000000');

        $response = $this->startTest();

        $this->assertContains(Event::ACCOUNT_ACTIVATED, $response);
        $this->assertContains(Event::ACCOUNT_REJECTED, $response);
        $this->assertContains(Event::ACCOUNT_SUSPENDED, $response);
        $this->assertContains(Event::ACCOUNT_UNDER_REVIEW, $response);
        $this->assertContains(Event::ACCOUNT_NEEDS_CLARIFICATION, $response);
        $this->assertContains(Event::ACCOUNT_ACTIVATED_KYC_PENDING, $response);
    }

    public function testGetWebhookEventsWithAccountStatusEventsBasedOnFeature()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::SUBMERCHANT_ONBOARDING]);

        $response = $this->startTest($this->testData['testGetWebhookEventsWithAccountStatusEventsForPurePlatformPartner']);

        $this->assertContains(Event::ACCOUNT_ACTIVATED, $response);
        $this->assertContains(Event::ACCOUNT_REJECTED, $response);
        $this->assertContains(Event::ACCOUNT_SUSPENDED, $response);
        $this->assertContains(Event::ACCOUNT_UNDER_REVIEW, $response);
        $this->assertContains(Event::ACCOUNT_NEEDS_CLARIFICATION, $response);
        $this->assertContains(Event::ACCOUNT_ACTIVATED_KYC_PENDING, $response);
    }

    public function testGetWebhookEventsWithAccountStatusEventsForAggregatorPartner()
    {
        $this->fixtures->merchant->markPartner('aggregator', '10000000000000');

        $response = $this->startTest($this->testData['testGetWebhookEventsWithAccountStatusEventsForPurePlatformPartner']);

        $this->assertNotContains(Event::ACCOUNT_ACTIVATED, $response);
        $this->assertNotContains(Event::ACCOUNT_REJECTED, $response);
        $this->assertNotContains(Event::ACCOUNT_SUSPENDED, $response);
        $this->assertNotContains(Event::ACCOUNT_UNDER_REVIEW, $response);
        $this->assertNotContains(Event::ACCOUNT_NEEDS_CLARIFICATION, $response);
        $this->assertNotContains(Event::ACCOUNT_ACTIVATED_KYC_PENDING, $response);
    }

    public function testGetWebhookEventsForDuplicateAccountStatusEvents()
    {
        $this->fixtures->merchant->markPartner('pure_platform', '10000000000000');
        $this->fixtures->merchant->addFeatures(['submerchant_onboarding']);

        $response = $this->startTest($this->testData['testGetWebhookEventsWithAccountStatusEventsForPurePlatformPartner']);

        $events = array_intersect($response, Event::$eventsApplicableBasedOnFeatureOrPartnerType);
        self::assertCount(6, $events);
    }

    private function setPurePlatformContext(): void
    {
        list($application) = $this->createPurePlatFormMerchantAndSubMerchant();

        $client = $this->getAppClientByEnv($application);

        $token = $this->generateOAuthAccessTokenForClient(
            [
                'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'scopes' => ['read_write'],
                'mode' => 'live',
            ],
            $client);

        $this->ba->oauthBearerAuth($token->toString());
    }
}
