<?php

namespace RZP\Tests\Functional\Merchant;

use Closure;
use Mockery;
use Mail;

use RZP\Mail\Merchant\Webhook as WebhookMail;
use Http\Mock\Client;
use RZP\Jobs\WebHook;
use RZP\Tests\Functional\TestCase;
use Http\Discovery\MessageFactoryDiscovery;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Models\Merchant\Webhook\Inferno;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\MockClientStrategy;
use Http\Client\Common\Exception\ClientErrorException;

/**
 * @group dns-sensitive
 */
class WebhookTest extends TestCase
{
    use PaymentTrait;
    use MocksDnsTrait;

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

    public function testEditWebhook()
    {
        $webhook = $this->createWebhook();

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->startTest();
    }

    public function testGetWebhooks()
    {
        $this->createWebhook();

        $this->startTest();
    }

    public function testCreateWebhookWrongUrl()
    {
        $data = $this->startTest();
    }

    public function testWebhookEventData()
    {
        $webhook = $this->createWebhook();

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

    public function testWebhookShouldNotFireWhenInactive()
    {
        $webhook = $this->createWebhook();

        $this->fixtures->edit('webhook', $webhook['id'], ['active' => 0]);

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
}
