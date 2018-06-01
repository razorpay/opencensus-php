<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Closure;
use Mockery;
use Carbon\Carbon;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Merchant\Webhook\Inferno;
use Http\Discovery\MessageFactoryDiscovery;
use RZP\Mail\Merchant\Webhook as WebhookMail;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use Http\Client\Common\Exception\ClientErrorException;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;

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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->setupMockDns();
    }

    public function testCreateWebhook()
    {
        $this->startTest();

        $webhook = $this->getDbLastEntity('webhook');

        $this->assertEquals(true, $webhook['disable_on_failure']);
    }

    public function testCreateWebhookWithdisableWebhookFalse()
    {
        $this->startTest();

        $webhook = $this->getDbLastEntity('webhook');

        $this->assertEquals(false, $webhook['disable_on_failure']);
    }

    public function testCreateWebhookWhenAlreadyCreated()
    {
        $this->fixtures->create('webhook');

        $this->startTest();
    }

    public function testCreateAppWebhook()
    {
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

    public function testCreateAppWebhookInvalidAppId()
    {
        $this->startTest();
    }

    public function testEditWebhook()
    {
        $webhook = $this->createWebhook();

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

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

    public function testGetWebhookEvents()
    {
        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $response = $this->startTest();

        $this->assertContains('order.paid', $response);
        $this->assertContains('virtual_account.credited', $response);
        $this->assertNotContains('subscription.charged', $response);
    }

    public function testGetAppWebhooks()
    {
        $this->createWebhook();

        $this->createApplicationWebhook('10000000000App');

        $this->createApplicationWebhook('1000000000App2');

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
        $this->assertEquals('http://example.com/v1/dummy/route', (string) $request->getUri());

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

    public function testWebhooksFeatureBasedEvents()
    {
        $this->createWebhook(['events' => ['payment.authorized' => '1', 'subscription.charged' => '1']]);

        $testData = $this->testData['testGetWebhooks'];

        $response = $this->startTest($testData);

        $events = $response['items'][0]['events'];

        $this->assertArrayNotHasKey('subscription.charged', $events);

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $testData = $this->testData['testGetWebhooks'];

        $response = $this->startTest($testData);

        $events = $response['items'][0]['events'];

        $this->assertArrayHasKey('subscription.charged', $events);;
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

    public function testWebhookDeactivationEmail()
    {
        $webhook = $this->createWebhook();
        $inferno = $this->mockInferno();

        $this->fixtures->edit(
            'webhook',
            $webhook['id'],
            [
                'last_successful_at' => (time() - (25 * 3600)),
                'active' => 1
            ]);

        $inferno->shouldReceive('sendRequest')
            ->once()
            ->andReturn(true);

        $inferno->shouldReceive('sendEmail')
            ->with(Mockery::type('object'), 'deactivate')
            ->once();

        $this->doAuthPayment();
    }

    public function testWebhookDeactivationEmailWithDisableFalse()
    {
        $webhook = $this->createWebhook();
        $inferno = $this->mockInferno();

        $this->fixtures->edit(
            'webhook',
            $webhook['id'],
            [
                'last_successful_at' => (time() - (25 * 3600)),
                'active' => 1,
                'disable_on_failure' => 0,
            ]);

        $inferno->shouldReceive('sendRequest')
            ->once()
            ->andReturn(true);

        $inferno->shouldNotHaveReceived('sendEmail');

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

    public function testWebhookDeactivation()
    {
        $webhook = $this->createWebhook();
        $inferno = $this->mockInferno();

        $this->fixtures->edit(
            'webhook', $webhook['id'], ['last_successful_at' => (time() - (25 * 3600)), 'active' => 1]);

        $inferno->shouldReceive('sendRequest')
                ->once()
                ->andReturn(true);

        $this->doAuthPayment();

        $webhook = $this->getLastEntity('webhook', true);

        $this->assertEquals($webhook['active'], false);
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

            $this->assertEquals('settlement.processed', $data['event']['event']);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        }, 2);

        $this->initiateSettlements($channel);

        $content = $this->initiateTransfer($channel, Attempt\Purpose::SETTLEMENT);

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

        $content = $this->initiateTransfer($channel, Attempt\Purpose::SETTLEMENT);

        $setlFile = $content[$channel]['file']['local_file_path'];

        $this->reconcileSettlementsForChannel($setlFile, $channel, true);

        $this->mockInfernoFire(function () { }, 0);

        $this->reconcileEntitiesForChannel($channel);
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
            'url'         => 'http://example.com/v1/dummy/route',
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
        $input = ['url' => 'http://sample.com/v1/dummy/route'];

        $input = array_merge($input, $params);

        $this->fixtures->create('webhook', $input);
    }

    protected function setClientTriggerAndVerifyRequests(array $eventDataKeys)
    {
        $client = $this->setInfernoMockClient();

        $this->doAuthPayment();

        $this->verifyRequestsData($client, $eventDataKeys);
    }
}
