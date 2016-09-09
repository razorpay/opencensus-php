<?php

namespace RZP\Tests\Functional\Merchant;

use Mockery;
use RZP\Jobs\WebHook;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant\Webhook\Inferno;

class WebhookTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WebhookData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateWebhook()
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

        $inferno = $this->mockInferno();

        $testData = $this->testData[__FUNCTION__];

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on(function ($data) use ($testData)
                        {
                            $data['event'] = json_decode($data['event'], true);
                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }));

        $this->app->instance('webhook.inferno', $inferno);

        $this->doAuthPayment();
    }

    public function testOrderPaidWebhookEventData()
    {
        $webhook = $this->createWebhook(['events' => ['order.paid' => "1"]]);

        $inferno = $this->mockInferno();

        $testData = $this->testData[__FUNCTION__];

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on(function ($data) use ($testData)
                        {
                            $data['event'] = json_decode($data['event'], true);

                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }));

        $this->app->instance('webhook.inferno', $inferno);

        $order = $this->fixtures->create('order', ['amount' => 50000, 'receipt' => 'random']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order->getPublicId();
        $payment['amount'] = $order->getAmount();

        $this->doAuthAndCapturePayment($payment);
    }

    public function testOrderPaidWebhookEventDataWithoutOrder()
    {
        $webhook = $this->createWebhook(['events' => ['order.paid' => "1"]]);

        $inferno = $this->mockInferno();

        $inferno->shouldReceive('fire')
                ->never();

        $this->app->instance('webhook.inferno', $inferno);

        $this->doAuthAndCapturePayment();
    }

    public function testDisableWebhookAfter3Attempts()
    {
        $webhook = $this->createWebhook();

        $inferno = $this->mockInferno();

        $response = new \Requests_Response;
        $response->status_code = '501';

        $inferno->shouldReceive('makeRequest')
//                ->times(3)
                ->andReturn($response);

        $this->app->instance('webhook.inferno', $inferno);

        $this->doAuthPayment();
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

        $lastSuccessfulAt = time() - (23*3600);

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

    public function testWebhookDeactivationEmail()
    {
        $webhook = $this->createWebhook();
        $inferno = $this->mockInferno();

        $this->fixtures->edit(
            'webhook', $webhook['id'], ['last_successful_at' => (time()-(25*3600)), 'active' => 1]);

        $inferno->shouldReceive('sendRequest')
            ->once()
            ->andReturn(false);

        $inferno->shouldReceive('sendEmail')
            ->with(Mockery::type('object'), 'deactivate')
            ->once();

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
        $webhook = $this->createWebhook();

        $inferno = $this->mockInferno();

        $testData = $this->testData[__FUNCTION__];

        $inferno->shouldReceive('makeRequest')
                ->once()
                ->with(Mockery::type('array'))
                ->andReturnUsing(function ($request) use ($testData)
                    {
                        $request['content'] = json_decode($request['content'], true);
                        $this->assertArraySelectiveEquals($testData, $request);
                        $response = $this->getStandardWebhookResponse();
                        return $response;
                    });

        $this->app->instance('webhook.inferno', $inferno);

        $this->doAuthPayment();
    }

    public function testWebhookFailureEmail()
    {
        $webhook = $this->createWebhook();
        $inferno = $this->mockInferno();

        $inferno->shouldReceive('sendRequest')
                ->once()
                ->andReturn(false);

        $inferno->shouldReceive('sendEmail')
                ->with(Mockery::type('object'),'failure')
                ->once();

        $this->doAuthPayment();
    }

    public function testSecretValueInWebhookEventDataJustBeforeFiring()
    {
        $webhook = $this->createWebhook(['secret'=>'test_secret']);

        $inferno = $this->mockInferno();

        $testData = $this->testData[__FUNCTION__];

        $inferno->shouldReceive('makeRequest')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturnUsing(function ($request) use ($testData, $webhook)
            {
                $request['content'] = json_decode($request['content'], true);

                $this->assertArraySelectiveEquals($testData, $request);

                $this->assertArrayHasKey('X-Razorpay-Signature', $request['headers']);

                $this->assertNotNull($request['headers']['X-Razorpay-Signature']);

                $response = $this->getStandardWebhookResponse();

                return $response;
            });

        $this->app->instance('webhook.inferno', $inferno);
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
        $actualValue = Inferno::generateHMAC($payload, NULL);
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

    protected function mockInfernoWithResponseStatusCode($statusCode, $method='makeRequest')
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

    protected function getStandardWebhookResponse($statusCode = 200)
    {
        $response = new \Requests_Response;
        $response->status_code = $statusCode;

        $success = false;

        if (($statusCode >= 200) and ($statusCode < 300))
        {
            $success = true;
        }

        $response->success = $success;

        return $response;
    }
}
