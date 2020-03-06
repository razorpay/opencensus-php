<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Closure;
use Mockery;
use Carbon\Carbon;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Feature;
use RZP\Models\Merchant\Webhook;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Webhook\Inferno;
use Illuminate\Database\Eloquent\Factory;
use Http\Discovery\MessageFactoryDiscovery;
use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use Http\Client\Common\Exception\ClientErrorException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Fixtures\Entity\Base as BaseFixture;
use RZP\Mail\Merchant\CreateSubMerchantPartner as CreateSubMerchantPartnerMail;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateMail;

/**
 * @group dns-sensitive
 */
class WebhookTest extends TestCase
{
    use AttemptTrait;
    use AttemptReconcileTrait;
    use MocksDnsTrait;
    use WebhookTrait;
    use DbEntityFetchTrait;
    use PartnerTrait;

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

            $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($value)
                {
                    if ($feature === Merchant\RazorxTreatment::DISABLE_WEBHOOK_UPDATE)
                    {
                        return 'off';
                    }
                }));

        $this->app->instance('razorx', $razorxMock);
    }

    public function mockRazorX(string $functionName, string $featureName, string $variant, $merchantId = '1cXSLlUU8V9sXl')
    {
        $testData = &$this->testData[$functionName];

        $uniqueLocalId = RazorXClient::getLocalUniqueId($merchantId, $featureName, Mode::TEST);

        $testData['request']['cookies'] = [RazorXClient::RAZORX_COOKIE_KEY => '{"' . $uniqueLocalId . '":"' . $variant . '"}'];

    }

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->ba->proxyAuth();

        $this->setupMockDns();

        $this->mockRazorX('diableWebhookUpdate', 'disable_webhook_update', 'off', 10000000000000);
    }

    public function testCreateWebhook()
    {
        $response = $this->startTest();

        // Events of other products (e.g. banking) should not come in response.
        $this->assertArrayNotHasKey('transaction.created', $response['events']);
        $this->assertArrayNotHasKey('payout.created', $response['events']);
        $this->assertArrayNotHasKey('payout.processed', $response['events']);
        $this->assertArrayNotHasKey('payout.reversed', $response['events']);

        $webhook = $this->getDbLastEntity('webhook');

        $this->assertEquals(true, $webhook['disable_on_failure']);
    }

    public function testCreateWebhookWhenAlreadyCreated()
    {
        $this->fixtures->create('webhook');

        $this->startTest();
    }

    /*
     * Partner type fully managed, can create webhook
     */
    public function testCreateAppWebhook()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);
        $this->startTest();
    }

    public function testCreateWebhookWithInternalIp()
    {
        $this->markTestSkipped();

        $this->startTest();
    }

    public function testCreateWebhookWithReservedIp()
    {
        $this->markTestSkipped();

        $this->startTest();
    }

    public function testCreateWebhookWithoutHost()
    {
        $this->startTest();
    }

    public function testCreateWebhookWithLargerSecret()
    {
        $this->startTest();
    }

    public function testCreateWebhookWithDisallowedPort()
    {
        $this->startTest();
    }

    public function testRecreateWebhook()
    {
        $this->createWebhook();

        $this->startTest();
    }

    /*
     * Invalid app id, cannot create webhook
     */
    public function testCreateAppWebhookInvalidAppId()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);
        $this->startTest();
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
        $this->startTest();
    }

    /*
     * Not partner yet but tagged OAuth, can create webhook
     */
    public function testCreateAppWebhookOAuthTag()
    {
        $this->addOAuthTag();
        $this->startTest();
    }

    /*
     * Partner type bank, also tagged OAuth, cannot create webhook
     * as this should ideally not happen and we should prevent by default
     */
    public function testCreateAppWebhookBankWithOAuthTag()
    {
        $this->addOAuthTag();
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'bank']);
        $this->startTest();
    }

    /*
     * Partner type fully managed, can create webhook irrespective
     * of the oauth tag
     */
    public function testCreateAppWebhookFullyManagedWithOAuthTag()
    {
        $this->addOAuthTag();
        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully_managed']);
        $this->startTest();
    }

    public function testCreateWebhookForProductBanking()
    {
        // This is required, because this is going to on board the merchant on X on the test mode
        // which requires the terminal entity to be present
        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        $this->fixtures->merchant->addFeatures(['payout']);

        $this->startTest();
    }

    public function testCreateWebhookForProductBankingWithInvalidEvents()
    {
        // This is required, because this is going to on board the merchant on X on the test mode
        // which requires the terminal entity to be present
        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        $this->fixtures->merchant->addFeatures(['payout']);

        $this->startTest();
    }

    public function testEditWebhook()
    {
        $webhook = $this->createWebhook();

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->startTest();
    }

    public function testEditWebhookByNonOwnerUser()
    {
        $webhook = $this->createWebhook();

        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'user_id'     => $user->id,
            'merchant_id' => '10000000000000',
            'role'        => 'support',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->toArrayPublic(), 'support');

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->startTest();
    }

    public function testEditDisableWebhookOnPrivateAuth()
    {
        $webhook = $this->createWebhook();

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->startTest();
    }

    public function testEditDisableWebhookOnAdminProxyAuth()
    {
        $webhook = $this->createWebhook();

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->ba->addAdminProxyAuthHeaders('10000000000000');

        $this->startTest();

        $webhook = $this->getDbEntityById('webhook', $webhook['id']);

        $this->assertFalse($webhook->isDisableOnFailure());
    }

    public function testEditWebhookForProductBankingWithInvalidEvents()
    {
        $this->testCreateWebhookForProductBanking();

        $webhookId = $this->getDbLastEntity('webhook')->getPublicId();
        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/' . $webhookId;

        $this->startTest();
    }

    public function testGetWebhooks()
    {
        $this->createWebhook();

        // Adding this to ensure only merchant webhooks are returned and no
        // application webhooks.
        $this->createApplicationWebhook('10000000000App');

        $response = $this->startTest();

        $this->assertNotContains('application_id', $response);
    }

    public function testGetWebhookWithSecret()
    {
        $webhook = $this->fixtures->create('webhook',
            [
                'merchant_id' => '10NodalAccount',
                'url'         => 'http://www.testUrl.com',
                'secret'      => 'BestTestSecretEver',
                'events'      => ['payment.authorized' => '1']
            ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->ba->hostedAuth('rzp_test_10NodalAccount');

        $response = $this->startTest();
    }

    public function testGetWebhooksWithSecret()
    {
        $this->fixtures->create('webhook',
            [
                'merchant_id' => '10NodalAccount',
                'url'         => 'http://www.testUrl.com',
                'secret'      => 'BestTestSecretEver',
                'events'      => ['payment.authorized' => '1']
            ]);

        $this->ba->hostedAuth('rzp_test_10NodalAccount');

        $response = $this->startTest();
    }

    public function testGetWebhookEvents()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $response = $this->startTest();

        $this->assertContains('order.paid', $response);
        $this->assertContains('virtual_account.credited', $response);
        $this->assertNotContains('subscription.charged', $response);

        // Events of other products (e.g. banking) should not come in response.
        $this->assertNotContains('transaction.created', $response);
        $this->assertNotContains('payout.created', $response);
        $this->assertNotContains('payout.processed', $response);
        $this->assertNotContains('payout.reversed', $response);
    }

    public function testGetAppWebhooks()
    {
        $this->createWebhook();

        $this->createApplicationWebhook('10000000000App');

        $this->createApplicationWebhook('1000000000App2');

        $this->startTest();
    }

    public function testGetWebhookEventsForProductBanking()
    {
        // This is required, because this is going to on board the merchant on X on the test mode
        // which requires the terminal entity to be present
        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
            ['merchant_id' => '100000Razorpay']);

        $this->fixtures->merchant->addFeatures(['payout']);

        $this->startTest();
    }

    public function testCreateWebhookWrongUrl()
    {
        $this->startTest();
    }

    public function testWebhookEventData()
    {
        $this->createWebhook();

        $testData = $this->testData[__FUNCTION__];

        $this->app['webhook.inferno']->setClient($this->app['httplug']->driver('mock'));

        $messageFactory = MessageFactoryDiscovery::find();

        $client = $this->app['webhook.inferno']->getClient();

        $response = $messageFactory->createResponse(200);
        $client->addResponse($response);

        $this->doAuthPayment();

        $request = $client->getRequests()[0];

        $this->assertEquals(['Razorpay-Webhook/v1'], $request->getHeader('User-Agent'));
        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));
        $this->assertEquals('http://webhook.com/v1/dummy/route', (string) $request->getUri());

        $body = (string) $request->getBody();
        $decodedBody = json_decode($body, true);

        $this->assertArraySelectiveEquals($testData, $decodedBody);
    }

    public function testInvoicePaidWebhookEventData()
    {
        $this->createWebhook(['events' => ['invoice.paid' => '1']]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            $paymentArray = $data['event']['payload']['payment']['entity'];
            $this->assertArrayNotHasKey('terminal_id', $paymentArray);

            return true;
        });

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice', ['amount' => 1000000]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

     /**
     * If partner parent has feature "terminal_onboarding" enabled, then only payment entity should have terminal_id key
     */
    public function testPaymentWebhookShouldHaveTerminalIdForFeaturedPartner()
    {
        $partner = $this->fixtures->create('merchant');

        $partnerId = $partner->getId();

        $this->fixtures->edit('merchant', $partnerId, ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => '10000000000000',
            'entity_owner_id' => $partnerId,
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            $partnerId
        );

        $this->fixtures->create('webhook',
        [
            'entity_type' => 'application',
            'entity_id'   => '10000000000App',
            'url'         => 'https://www.razorpay.co.in',
            'events'      => [
                'invoice.paid' => '1'
            ]
        ]);

        $testData = $this->testData['testInvoicePaidWebhookEventData'];

        $testData['event']['payload']['payment']['entity']['terminal_id'] = 'term_1n25f6uN5S1Z5a';

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice', ['amount' => 1000000]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    /**
     * Invoice created without customer details, once paid should contain those
     * information in invoices entity and same should be sent as hook payload.
     *
     */
    public function testInvoiceWithoutCustomerDetailsPaidWebhookEventData()
    {
        $this->createWebhook(['events' => ['invoice.paid' => '1']]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice',
            [
                'amount'           => 1000000,
                'customer_id'      => null,
                'customer_name'    => null,
                'customer_email'   => null,
                'customer_contact' => null,
            ]);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    public function testInvoicePaidWebhookEventDataWithOrderAndWithoutInvoice()
    {
        $this->createWebhook(['events' => ['order.paid' => '1', 'invoice.paid' => '1']]);

        $testData = $this->testData[__FUNCTION__];

        // This webhook will be called for order.paid event.
        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);
            $this->assertArrayNotHasKey('invoice', $data['event']);

            return true;
        });

        $order = $this->fixtures->create('order', ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testInvoicePaidWebhookEventNotEnabled()
    {
        $this->createWebhook(['events' => ['order.paid' => "1"]]);

        $inferno = $this->mockInferno();

        // It should be called only once - for order.paid
        $inferno->shouldReceive('fire')
                ->once();

        $this->app->instance('webhook.inferno', $inferno);

        $order = $this->fixtures->create('order',
                    [
                        'id'              => '100000000order',
                        'receipt'         => 'random',
                        'payment_capture' => true,
                    ]);

        $this->fixtures->create('invoice');

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $order->getPublicId();
        $payment['amount']   = $order->getAmount();

        $this->doAuthPayment($payment);
    }

    public function testCreateWebhookWithEventWhenFeatureNotEnabled()
    {
        // Subscribing to subscription.charged requires subscription feature to be enabled and hence expected failure.
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        $this->expectExceptionMessage('Invalid event name/names: subscription.charged');

        $this->createWebhook(
            [
                'events' => [
                    'payment.authorized'   => '1',
                    'subscription.charged' => '1',
                ],
            ]);
    }

    public function testWebhooksFeatureBasedEvents()
    {
        // Adds feature and creates webhook with subscriptions.charged even and asserts the same in next get call.

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->createWebhook(['events' => ['payment.authorized' => '1', 'subscription.charged' => '1']]);

        $testData = $this->testData['testGetWebhooks'];

        $response = $this->startTest($testData);

        $events = $response['items'][0]['events'];

        $this->assertArrayHasKey('subscription.charged', $events);

        //
        // Hypothetically, for backward compatibility, if the events were added by mistake or via some other unknown flow,
        // the same must not be exposed still in get/list requests.
        //

        $this->fixtures->merchant->removeFeatures(['subscriptions']);

        $testData = $this->testData['testGetWebhooks'];

        $response = $this->startTest($testData);

        $events = $response['items'][0]['events'];

        $this->assertArrayNotHasKey('subscription.charged', $events);
    }

    public function testWebhookEventWithExpressTranslationEnabled()
    {
        $translatedWebhookBody = 'sample translated webhook body';

        $webhookSecret = 'sample_secret';

        // mark as partner
        $partnerId     = '100000Razorpay';
        $client        = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);
        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
                'entity_owner_id' => $partnerId,
            ]
        );

        $app = DB::Connection('auth')
                 ->table('applications')
                 ->orderBy('created_at', 'desc')
                 ->first();

        // create partner webhook
        $this->createMerchantWebhook(
            [
                'events'      => ['payment.authorized' => "1"],
                'secret'      => $webhookSecret,
                'entity_type' => 'application',
                'entity_id'   => $app->id,
            ]);

        // create setting for translation url
        $this->ba->adminAuth();
        $this->fixtures->edit('admin', 'RzrpySprAdmnId', ['allow_all_merchants' => 1]);
        $this->ba->addAccountAuth($partnerId);

        $testData = $this->testData['createSettingsForWebhookTranslateUrl'];

        $this->runRequestResponseFlow($testData);

        $this->ba->deleteAccountAuth();

        $payment  = $this->getDefaultPaymentArray();

        // mock mozart webhook translate and inferno requests
        $this->mockMozartWebhookTranslateRequest(function ($path, $content) use ($translatedWebhookBody) {

            return [
                'content'   => $translatedWebhookBody,
                'headers'   => ['request-id' => ['12345678']],
            ];
        });

        $webhookFired = [];

        $this->mockInfernoMakeRequest(function ($request) use (& $webhookFired)
        {
            $webhookFired = $request;

            return $this->getStandardWebhookResponse();
        });

        // make payment on submerchant
        $this->doPartnerAuthPayment($payment, $client->getId(), $submerchantId);

        /*
         * these asserts cannot be inside the mockInfernoMakeRequest closure because
         * if assert fails, then exception is thrown. However, the exception is caught and not rethrown
         * by inferno. this leads to all assert failures failing silently.
         */
        $this->assertEquals($translatedWebhookBody, $webhookFired['content']);

        $this->assertEquals('12345678', $webhookFired['headers']['request-id'][0]);

        $this->assertEquals(
            hash_hmac('sha256', $translatedWebhookBody, $webhookSecret),
            $webhookFired['headers']['X-Razorpay-Signature']);

        // to assert that express service does not modify the original url, method etc
        $this->assertEquals('http://webhook.com/v1/dummy/route', $webhookFired['url']);

        $this->assertEquals('post', $webhookFired['method']);
    }

    public function testWebhookEventWithExpressTranslationNotEnabled()
    {
        $this->ba->privateAuth();

        $this->createMerchantWebhook(['events' => ['payment.captured' => "1"]]);

        $this->mockMozartWebhookTranslateRequest(null, 0);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testWebhookPaymentCreated()
    {
        $translatedWebhookBody = 'sample translated webhook body';

        $webhookSecret = 'sample_secret';

        // mark as partner
        $partnerId     = '100000Razorpay';
        $client        = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);
        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
                'entity_owner_id' => $partnerId,
            ]
        );

        $app = DB::Connection('auth')
            ->table('applications')
            ->orderBy('created_at', 'desc')
            ->first();

        // create partner webhook
        $this->createMerchantWebhook(
            [
                'events'      => ['payment.created' => "1"],
                'secret'      => $webhookSecret,
                'entity_type' => 'application',
                'entity_id'   => $app->id,
            ]);

        // create setting for translation url
        $this->ba->adminAuth();
        $this->fixtures->edit('admin', 'RzrpySprAdmnId', ['allow_all_merchants' => 1]);
        $this->ba->addAccountAuth($partnerId);

        $testData = $this->testData['createSettingsForWebhookTranslateUrl'];

        $this->runRequestResponseFlow($testData);

        $this->ba->deleteAccountAuth();

        $payment  = $this->getDefaultPaymentArray();

        // mock mozart webhook translate and inferno requests
        $this->mockMozartWebhookTranslateRequest(function ($path, $content) use ($translatedWebhookBody) {

            return [
                'content'   => $translatedWebhookBody,
                'headers'   => ['request-id' => ['12345678']],
            ];
        });

        $webhookFired = [];

        $this->mockInfernoMakeRequest(function ($request) use (& $webhookFired)
        {
            $webhookFired = $request;

            return $this->getStandardWebhookResponse();
        });

        // make payment on submerchant
        $this->doPartnerAuthPayment($payment, $client->getId(), $submerchantId);

        /*
         * these asserts cannot be inside the mockInfernoMakeRequest closure because
         * if assert fails, then exception is thrown. However, the exception is caught and not rethrown
         * by inferno. this leads to all assert failures failing silently.
         */
        $this->assertEquals($translatedWebhookBody, $webhookFired['content']);

        $this->assertEquals('12345678', $webhookFired['headers']['request-id'][0]);

        $this->assertEquals(
            hash_hmac('sha256', $translatedWebhookBody, $webhookSecret),
            $webhookFired['headers']['X-Razorpay-Signature']);

        // to assert that express service does not modify the original url, method etc
        $this->assertEquals('http://webhook.com/v1/dummy/route', $webhookFired['url']);

        $this->assertEquals('post', $webhookFired['method']);
    }

    public function testOrderPaidWebhookEventData()
    {
        $this->createWebhook(['events' => ['order.paid' => "1"]]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        $order = $this->fixtures->create('order', ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testOrderPaidWebhookEventDataWithoutOrder()
    {
        $this->createWebhook(['events' => ['order.paid' => "1"]]);

        $inferno = $this->mockInferno();

        $inferno->shouldReceive('fire')
                ->never();

        $this->app->instance('webhook.inferno', $inferno);

        $this->doAuthAndCapturePayment();
    }

    public function testAppAndMerchantWebhook()
    {
        $this->fixtures->create('merchant_access_map');

        // 1 app and 1 merchant webhook. Should fire 2.
        $this->createApplicationWebhook('10000000000App', false);

        $this->createMerchantWebhook();

        $eventDataKeys = ['testMerchantWebhookData', 'testAppWebhookData'];

        $this->setClientTriggerAndVerifyRequests($eventDataKeys);
    }

    public function testAppWebhookWithInactiveMerchantWebhook()
    {
        $this->fixtures->create('merchant_access_map');

        // 1 active app webhook
        $this->createApplicationWebhook('10000000000App', false);

        $input = ['active' => 0];

        // 1 inactive merchant webhook. Should fire 1.
        $this->createMerchantWebhook($input);

        $eventDataKeys = ['testAppWebhookData'];

        $this->setClientTriggerAndVerifyRequests($eventDataKeys);
    }

    public function testMerchantWebhookWithInactiveAppWebhook()
    {
        $this->fixtures->create('merchant_access_map');

        $input = ['active' => 0];

        // 1 inactive app webhook and active merchant webhook. Should fire 1.
        $this->createApplicationWebhook('10000000000App', false, $input);

        $this->createMerchantWebhook();

        $eventDataKeys = ['testMerchantWebhookData'];

        $this->setClientTriggerAndVerifyRequests($eventDataKeys);
    }

    public function testMultipleAppWebhooks()
    {
        $this->fixtures->create('merchant_access_map');

        $this->fixtures->create('merchant_access_map', ['entity_id' => '10000000001App']);

        // 2 active app webhooks. Should fire 2.
        $this->createApplicationWebhook('10000000000App', false);

        $input = ['url' => 'http://exampleapp.com/v1/dummy/route'];

        $this->createApplicationWebhook('10000000001App', false, $input);

        $eventDataKeys = ['testAppWebhookData', 'testApp2WebhookData'];

        $this->setClientTriggerAndVerifyRequests($eventDataKeys);
    }

    public function testMultipleAppWebhooksWithInactiveWebhook()
    {
        $this->fixtures->create('merchant_access_map');

        $this->fixtures->create('merchant_access_map', ['entity_id' => '10000000001App']);

        // 1 active and 1 inactive app webhook. Should fire 1.
        $this->createApplicationWebhook('10000000000App', false);

        $input = ['active' => 0, 'url' => 'http://exampleapp.com/v1/dummy/route'];

        $this->createApplicationWebhook('10000000001App', false, $input);

        $eventDataKeys = ['testAppWebhookData'];

        $this->setClientTriggerAndVerifyRequests($eventDataKeys);
    }

    public function testWebhookShouldNotFireWhenInactive()
    {
        $webhook = $this->createWebhook();

        $this->fixtures->edit('webhook', $webhook['id'], ['active' => 0]);

        $inferno = $this->mockInferno();

        $inferno->shouldNotReceive('fire');

        $this->doAuthPayment();
    }

    public function testAppWebhookShouldNotFireWhenInactive()
    {
        $this->fixtures->create('merchant_access_map');

        $input = ['active' => 0];

        // 1 inactive app webhook and 1 inactive merchant webhook. Should fire 0.
        $webhook = $this->fixtures->create('webhook', $input);

        $this->createApplicationWebhook('10000000000App', false, $input);

        $inferno = $this->mockInferno();

        $inferno->shouldNotReceive('fire');

        $this->doAuthPayment();
    }

    public function testWebhookResetLastSuccessfulAtAfterSuccessfulFiring()
    {
        $webhook = $this->createWebhook();

        $lastSuccessfulAt = time() - (23 * 3600);

        $this->fixtures->edit(
            'webhook', $webhook['id'], ['last_successful_at' => $lastSuccessfulAt, 'active' => 1]);

        $this->mockInfernoWithResponseStatusCode(200);

        $this->doAuthPayment();

        $webhook = $this->getLastEntity('webhook', true);

        // In case it takes 3 seconds to make the mock request.
        $this->assertGreaterThan((time() - (3)), $webhook['last_successful_at']);
        $this->assertNotEquals($lastSuccessfulAt, $webhook['last_successful_at']);
        $this->assertEquals(true, $webhook['active']);
    }

    public function testWebhookResponseStatusCodes()
    {
        $this->createWebhook();

        // Webhook response with status code 200
        $inferno = $this->mockInfernoWithResponseStatusCode(200);

        // Is considered a success
        $inferno->shouldReceive('sendRequest')
                ->once()
                ->andReturn(false);

        $this->doAuthPayment();

        // Webhook response with status code 204
        $inferno = $this->mockInfernoWithResponseStatusCode(204);

        // Is also considered a success
        $inferno->shouldReceive('sendRequest')
                ->once()
                ->andReturn(false);

        $this->doAuthPayment();

        // Webhook response with status code 200
        $inferno = $this->mockInfernoWithResponseStatusCode(400);

        // Is not considered a success
        $inferno->shouldReceive('sendRequest')
                ->once()
                ->andReturn(true);

        $this->doAuthPayment();
    }

    public function testExceptionOnWebhookFire()
    {
        $webhook = $this->createWebhook(['secret' => 'test_secret']);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoMakeRequest(function ($request) use ($testData, $webhook)
        {
            $response = $this->getStandardWebhookResponse('400');

            $requestObj = $this->getStandardWebhookRequest($request);

            throw new ClientErrorException('Bad Request', $requestObj, $response);
        });

        $inferno = $this->app['webhook.inferno'];

        $inferno->shouldReceive('sendEmail')
                ->with(Mockery::type('object'), 'failure')
                ->andReturn(false);

        $this->doAuthPayment();
    }

    public function testWebhookHittingTheDefinedRoute()
    {
        $this->markTestSkipped();

        $webhook = $this->createWebhook();

        $this->doAuthPayment();
    }

    public function testWebhookEventDataJustBeforeFiring()
    {
        $this->createWebhook();

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoMakeRequest(function ($request) use ($testData)
        {
            $request['content'] = json_decode($request['content'], true);
            $this->assertArraySelectiveEquals($testData, $request);
            $response = $this->getStandardWebhookResponse();

            return $response;
        });

        $this->doAuthPayment();
    }

    public function testWebhookFailureEmail()
    {
        Mail::fake();

        $webhook = $this->createWebhook();
        $inferno = $this->mockInferno();

        $inferno->shouldReceive('sendRequest')
                ->once()
                ->andReturn(true);

        $this->doAuthPayment();

        Mail::assertSent(WebhookMail::class);
    }

    public function testSecretValueInWebhookEventDataJustBeforeFiring()
    {
        $webhook = $this->createWebhook(['secret' => 'test_secret']);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoMakeRequest(function ($request) use ($testData, $webhook)
        {
            $request['content'] = json_decode($request['content'], true);

            $this->assertArraySelectiveEquals($testData, $request);

            $this->assertArrayHasKey('X-Razorpay-Signature', $request['headers']);

            $this->assertNotNull($request['headers']['X-Razorpay-Signature']);

            $response = $this->getStandardWebhookResponse();

            return $response;
        });

        $this->doAuthPayment();
    }

    public function testGenerateHmac()
    {
        $payload = 'a';
        $secret = 'b';

        $expectedValue = hash_hmac('sha256', $payload, $secret);

        $actualValue = Inferno::generateHMAC($payload, $secret);

        $this->assertEquals($expectedValue, $actualValue);
    }

    public function testGenerateHmacWithNullSecret()
    {
        $payload = 'a';
        $actualValue = Inferno::generateHMAC($payload, null);

        $this->assertNull($actualValue);
    }

    public function testGenerateHmacWithNonStringPayload()
    {
        $secret = 'a';
        $payload = ['a' => 'b'];

        try
        {
            Inferno::generateHMAC($payload, $secret);
        }
        catch(\Exception $ex)
        {
            $this->assertEquals($ex->getCode(), 0);

            return;
        }
        self::fail();
    }

    /**
     * Tests if a webhook is triggered to the merchant when a settlement is processed.
     */
    public function testTransferSettlementWebhook()
    {
        $this->ba->privateAuth();

        $channel = Settlement\Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $payment = $this->createPaymentEntities(1);

        $account2 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000002']);
        $this->fixtures->merchant->edit('10000000000002', ['channel' => $channel]);

        $this->createTransferEntity($payment, $account2);

        $account3 = $this->fixtures->create('merchant:marketplace_account', ['id' => '10000000000003']);
        $this->fixtures->merchant->edit('10000000000003', ['channel' => $channel]);

        $this->createTransferEntity($payment, $account3);

        $this->createWebhook(
            [
                'events' => [
                    'settlement.processed' => '1',
                ]
            ]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArrayHasKey('account_id', $data['event']);
            // Asserts that the account_id in event payload is one of the linked accounts.
            $this->assertContains($data['event']['account_id'], ['acc_10000000000002', 'acc_10000000000003']);

            $this->assertEquals('settlement.processed', $data['event']['event']);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        }, 2);

        $this->initiateSettlements($channel);

        $content = $this->initiateTransfer($channel,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->reconcileSettlementsForChannel($setlFile, $channel, false);

        $this->reconcileEntitiesForChannel($channel);

        $this->reconcileEntitiesForChannel($channel);
    }

    public function testWebhookOnSettlementFailure()
    {
        $channel = Settlement\Channel::ICICI;

        $this->fixtures->merchant->edit('10000000000000', ['channel' => $channel]);

        $this->createPaymentAndRefundEntities(2);

        $this->initiateSettlements($channel);

        $content = $this->initiateTransfer($channel,
            Attempt\Purpose::SETTLEMENT,
            Attempt\Type::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->reconcileSettlementsForChannel($setlFile, $channel, true);

        $this->mockInfernoFire(function () { }, 0);

        $this->reconcileEntitiesForChannel($channel);
    }

    public function testRefundSpeedChangedWebhookEventData()
    {
        $this->fixtures->merchant->addFeatures(['card_transfer_refund']);

        $this->createWebhook(['events' => ['refund.speed_changed' => '1']]);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null) {
            if ($action === 'verify') {
                $content['result'] = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2'] = '';
                $content['udf5'] = 'TrackID';
            }

            if ($action === 'refund') {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $this->refundPayment($payment['id'], 3470, ['speed' => 'optimum', 'is_fta' => true]);
    }

    public function testRefundFailedWebhookEventData()
    {
        $this->fixtures->merchant->addFeatures(['show_refund_public_status']);

        $this->createWebhook(['events' => ['refund.failed' => '1']]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            if($action === 'refund')
            {
                $content['result'] = 'DENIED BY RISK';
            }

            return $content;
        });

        // Adding specific amount to refund - this is meant to test failed refunds on scrooge -
        // in which case we have reversal of refund transactions as well
        $refund = $this->refundPayment($payment['id'], 3459);

        $this->assertEquals('rfnd_', substr($refund['id'], 0, 5));

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(false, $refund['gateway_refunded']);
        $this->assertEquals('reversed', $refund['status']);

        $reversal = $this->getLastEntity('reversal', true);

        $this->assertEquals($reversal['entity_type'], 'refund');
        $this->assertEquals('rfnd_'.$reversal['entity_id'], $refund['id']);
        $this->assertNotNull($reversal['balance_id']);
    }

    public function testRefundProcessedInstantWebhookEventData()
    {
        $this->fixtures->merchant->addFeatures(['card_transfer_refund']);

        $this->createWebhook(['events' => ['refund.processed' => '1']]);

        $this->fixtures->pricing->createInstantRefundsPricingPlan();

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $card = $this->getDbLastEntity('card');

        $iin = $this->getDbEntityById('iin', $card['iin']);

        $this->assertEquals($iin['type'], 'credit');

        $this->assertEquals($iin['issuer'], 'HDFC');

        $this->fixtures->card->edit($payment['card_id'], ['vault_token' => 'XXXXXXXXXXX']);
        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        // Adding specific amount to refund - this is meant to test processed instant refunds on scrooge -
        $this->refundPayment($payment['id'], 3471, ['speed' => 'optimum', 'is_fta' => true]);
    }

    public function testRefundProcessedNormalWebhookEventData()
    {
        $this->fixtures->merchant->addFeatures(['card_transfer_refund']);

        $this->createWebhook(['events' => ['refund.processed' => '1']]);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        $this->refundPayment($payment['id']);
    }

    public function testRefundCreatedWebhookEventData()
    {
        $this->createWebhook(['events' => ['refund.created' => '1']]);

        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->gateway = 'hdfc';

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['result']       = 'FAILURE(SUSPECT)';
                $content['authRespCode'] = 'J';
                $content['udf2']         = '';
                $content['udf5']         = 'TrackID';
            }

            return $content;
        });

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        $this->refundPayment($payment['id']);
    }

    public function testRefundCreatedWebhookForAggregatorModel()
    {
        // To test refund.created webhook for an parent merchant whose child merchant will initiate a refund.
        // Creating an aggregator merchant and sub merchant

        Mail::fake();

        $this->fixtures->merchant->edit('10000000000000', ['partner_type' => 'aggregator']);

        $app = $this->createOAuthApplication(['merchant_id' => '10000000000000', 'type' => 'partner']);

        $client = $this->getAppClientByEnv($app, 'dev');

        $this->createApplicationWebhook($app->getId(), false);

        $webhookId = $this->getDBLastEntity('webhook')->toArray()['id'];

        $this->fixtures->webhook->edit($webhookId, ['events' => ['refund.created' => '1']]);

        $configAttributes = [
            PartnerConfig\Entity::DEFAULT_PLAN_ID => Pricing::DEFAULT_PRICING_PLAN_ID,
        ];

        $this->createConfigForPartnerApp($app->getId(), null, $configAttributes);

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->startTest($this->testData['testCreateSubMerchantByAggregatorWithEmail']);

        Mail::assertQueued(CreateSubMerchantPartnerMail::class, function ($mail)
        {
            return $mail->hasTo('test@razorpay.com');
        });

        Mail::assertQueued(CreateSubMerchantAffiliateMail::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('org_100000razorpay', $data['org']['id']);

            return $mail->hasTo('testsub@razorpay.com', 'Submerchant');
        });

        $submerchant = $this->getLastEntity('merchant', true);

        $this->fixtures->user->getMerchantUserMapping($submerchant['id'], 'MerchantUser01');

        // Child merchant payment
        $this->ba->partnerAuth($submerchant['id'], 'rzp_test_partner_' . $client->getId(), $client->getSecret());

        $this->fixtures->create('terminal', [
            'enabled' => true,
            'merchant_id' => $submerchant['id'],
            'mc_mpan' => '1234567890123456',
            'visa_mpan' => '9876543210123456',
            'rupay_mpan' => '1234123412341234',
            'notes'     => 'some notes'
        ]);

        $payment = $this->defaultAuthPayment();

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $request = array(
            'method'  => 'POST',
            'url'     => '/payments/' . $payment['id'] . '/capture',
            'content' => array('amount' => $payment['amount']));

        $content = $this->makeRequestAndGetContent($request);

        $this->assertArrayHasKey('amount', $content);
        $this->assertArrayHasKey('status', $content);

        $this->assertEquals($content['status'], 'captured');

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);
            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            return true;
        });

        // Child merchant initiates the refund

        $this->refundPayment($payment['id'], $payment['amount']/2, [], [], false, ['key' => 'rzp_test_partner_' . $client->getId(), 'secret' => $client->getSecret()]);
    }

    public function testTerminalOnboardingVerificationWebhook()
    {
        $this->app['config']->set('worldline_terminal_onboarding_verification.case', "1");

        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $subMerchant->setCategory("742");

        $subMerchant->save();

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' =>  $subMerchantId,
                'submitted'   => true,
                'locked'      => true
            ]);


        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            '10000000000000'
        );

        // Adding merchant 10000000000000 's appId (10000000000App) in webhook entity_id
        $this->fixtures->create('webhook',
            [
                'entity_type' => 'application',
                'entity_id'   => '10000000000App',
                'url'         => 'https://www.razorpay.co.in',
                'events'      => [
                    'terminal.activated' => '1'
                ]
            ]);

        $terminal = $this->fixtures->create('terminal',
            [
                'merchant_id' => $subMerchantId,
                'enabled'     => false,
                'gateway'     => 'worldline',
                'status'      => 'pending'
            ]);

        $activationTime = Carbon::now()->subMinutes(10);

        $this->fixtures->create('terminal_onboarding_detail',
            [
                'terminal_id'       => $terminal->getId(),
                'status'            => 'pending',
                'verify_bucket'     => 0,
                'verify_at'         => $activationTime->getTimestamp(),
            ]);

        $this->ba->cronAuth();

        $testData = $this->testData[__FUNCTION__ . 'Data'];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $this->assertEquals('terminal.activated', $data['event_name']);
            $this->assertArrayHasKey('webhook_id', $data);

            $data['event'] = json_decode($data['event'], true);
            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->startTest();
    }

    public function testTerminalOnboardingCreationFailedWebhook()
    {
        $this->app['config']->set('worldline_terminal_onboarding_creation.case', "5");

        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $subMerchant->setCategory("742");

        $subMerchant->save();

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' =>  $subMerchantId,
                'submitted'   => true,
                'locked'      => true
            ]);

        (new BaseFixture)->createEntityInTestAndLive('merchant_detail', [
            'merchant_id' => '10000000000000',
            'submitted'   => true,
            'business_registered_state' => 'KA',
            'locked'      => true
        ]);

        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            '10000000000000'
        );

        // Adding merchant 10000000000000 's appId (10000000000App) in webhook entity_id
        $this->fixtures->create('webhook',
            [
                'entity_type' => 'application',
                'entity_id'   => '10000000000App',
                'url'         => 'https://www.razorpay.co.in',
                'events'      => [
                    'terminal.failed' => '1'
                ]
            ]);

        $terminal = $this->fixtures->create('terminal',
            [
                'merchant_id' => $subMerchantId,
                'enabled'     => false,
                'gateway'     => 'worldline',
                'status'      => 'created'
            ]);

        $this->fixtures->create('terminal_onboarding_detail',
            [
                'terminal_id'       => $terminal->getId(),
                'status'            => 'created',
                'verify_bucket'     => 0,
            ]);

        $this->ba->cronAuth();

        $testData = $this->testData[__FUNCTION__ . 'Data'];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $this->assertEquals('terminal.failed', $data['event_name']);
            $this->assertArrayHasKey('webhook_id', $data);

            $data['event'] = json_decode($data['event'], true);
            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->startTest();
    }

    public function testTerminalOnboardingActivationFailedWebhook()
    {
        $this->app['config']->set('worldline_terminal_onboarding_verification.case', "2");

        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $subMerchant->setCategory("742");

        $subMerchant->save();

        $this->fixtures->create('merchant_detail',
            [
                'merchant_id' => $subMerchantId,
                'submitted'   => true,
                'locked'      => true
            ]);


        $this->fixtures->merchant->addFeatures(
            [Feature\Constants::TERMINAL_ONBOARDING],
            '10000000000000'
        );

        // Adding merchant 10000000000000 's appId (10000000000App) in webhook entity_id
        $this->fixtures->create('webhook',
            [
                'entity_type' => 'application',
                'entity_id'   => '10000000000App',
                'url'         => 'https://www.razorpay.co.in',
                'events'      => [
                    'terminal.failed' => '1'
                ]
            ]);

        $terminal = $this->fixtures->create('terminal',
            [
                'merchant_id' => $subMerchantId,
                'enabled'     => false,
                'gateway'     => 'worldline',
                'status'      => 'pending'
            ]);

        $activationTime = Carbon::now()->subMinutes(10);

        $this->fixtures->create('terminal_onboarding_detail',
            [
                'terminal_id'       => $terminal->getId(),
                'status'            => 'pending',
                'verify_bucket'     => 100,
                'verify_at'         => $activationTime->getTimestamp(),
            ]);

        $this->ba->cronAuth();

        $testData = $this->testData[__FUNCTION__ . 'Data'];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $this->assertEquals('terminal.failed', $data['event_name']);
            $this->assertArrayHasKey('webhook_id', $data);

            $data['event'] = json_decode($data['event'], true);
            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->startTest();
    }

    public function testWebhookDeactivate()
    {
        Mail::fake();

        $this->createMerchantWebhook();

        $webhook = $this->getLastEntity('webhook', false);

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'] . '/deactivate';

        $this->startTest();

        // test webhook deactivate
        $webhookExpected = $this->getEntityById('webhook',$webhook['id']);

        $this->assertEquals($webhookExpected['active'],false);

        $testData = $this->testData[__FUNCTION__.'Data'];

        // test mail sent
        Mail::assertQueued(WebhookMail::class, function ($mail) use ($testData)
        {
            $this->assertEquals($mail->viewData['url'], $testData['url']);

            $this->assertEquals($mail->viewData['mode'], $testData['mode']);

            $this->assertEquals($mail->viewData['subject'], $testData['subject']);

            return ($mail->hasFrom('alerts@razorpay.com') and ($mail->hasTo('test@razorpay.com')));
        });
    }


    protected function createTransferEntity($payment, $account)
    {
        $createdAt = Carbon::today(Timezone::IST)->subDays(20)->timestamp + 5;

        $this->fixtures->create('transfer:to_account',
            [
                'account'       => $account,
                'source_id'     => $payment->getId(),
                'source_type'   => 'payment',
                'amount'        => 2500,
                'currency'      => 'INR',
                'on_hold'       => '0',
                'on_hold_until' => Carbon::today(Timezone::IST)->timestamp - 600,
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt + 10
            ]);
    }

    protected function mockInfernoWithResponseStatusCode($statusCode, $method = 'makeRequest')
    {
        $inferno = $this->mockInferno();

        $response = $this->getStandardWebhookResponse($statusCode);

        $inferno->shouldReceive($method)
                ->andReturn($response);

        return $inferno;
    }

    protected function mockInferno()
    {
        $class = \RZP\Models\Merchant\Webhook\Inferno::class;

        $inferno = Mockery::mock($class, [])->makePartial();

        $this->app->instance('webhook.inferno', $inferno);

        return $inferno;
    }

    protected function mockInfernoMakeRequest(Closure $closure, $times = 1)
    {
        $inferno = $this->mockInferno();

        $inferno->shouldReceive('makeRequest')
                ->times($times)
                ->with(Mockery::type('array'))
                ->andReturnUsing($closure);

        $this->app->instance('webhook.inferno', $inferno);
    }

    protected function mockInfernoFire(Closure $closure, $times = 1)
    {
        $inferno = $this->mockInferno();

        $inferno->shouldReceive('fire')
                ->times($times)
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }

    protected function getStandardWebhookResponse($statusCode = 200): ResponseInterface
    {
        $response = new \GuzzleHttp\Psr7\Response($statusCode);

        return $response;
    }

    protected function getStandardWebhookRequest(array $requestData): RequestInterface
    {
        $request = new \GuzzleHttp\Psr7\Request(
            $requestData['method'],
            $requestData['url'],
            $requestData['headers'],
            $requestData['content']);

        return $request;
    }

    protected function createApplicationWebhook(
        string $appId,
        bool $defaultMerchant = true,
        array $params = [])
    {
        $input = [
            'entity_type' => 'application',
            'entity_id'   => $appId,
            'url'         => 'http://webhook.com/v1/dummy/route',
        ];

        if ($defaultMerchant === false)
        {
            $input['merchant_id'] = '100000Razorpay';
        }

        $input = array_merge($input, $params);

        $this->fixtures->create('webhook', $input);
    }

    protected function createMerchantWebhook(array $params = [])
    {
        $input = ['url' => 'http://webhook.com/v1/dummy/route'];

        $input = array_merge($input, $params);

        $this->fixtures->create('webhook', $input);
    }

    protected function setClientTriggerAndVerifyRequests(array $eventDataKeys)
    {
        $client = $this->setInfernoMockClient();

        $this->doAuthPayment();

        $this->verifyRequestsData($client, $eventDataKeys);
    }

    protected function addOAuthTag(string $merchantId = '10000000000000')
    {
        $merchant = Merchant\Entity::find($merchantId);
        $merchant->reTag(["oauth"]);
        $merchant->saveOrFail();
    }
}
