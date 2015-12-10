<?php

namespace Tests\Functional\Merchant;

use Mockery;
use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

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
                    Mockery::type('Illuminate\Queue\Jobs\Job'),
                    Mockery::on(function ($data) use ($testData)
                        {
                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }));

        $this->app->instance('webhook.inferno', $inferno);

        $this->doAuthPayment();
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

    public function testWebhookDisableOn3Failures()
    {
        $webhook = $this->createWebhook();

        $this->fixtures->edit('webhook', $webhook['id'], ['failure_count' => 2]);

        $this->mockInfernoWithResponseStatusCode('501');

        $this->doAuthPayment();

        $webhook = $this->getLastEntity('webhook', true);
        $this->assertEquals(3, $webhook['failure_count']);
        $this->assertEquals(false, $webhook['active']);
    }

    public function testWebhookResetFailureCountAfterSuccessfulFiring()
    {
        $webhook = $this->createWebhook();

        $this->fixtures->edit(
            'webhook', $webhook['id'], ['failure_count' => 2, 'active' => 1]);

        $this->mockInfernoWithResponseStatusCode('200');

        $this->doAuthPayment();

        $webhook = $this->getLastEntity('webhook', true);
        $this->assertEquals(0, $webhook['failure_count']);
        $this->assertEquals(true, $webhook['active']);
    }

    protected function mockInfernoWithResponseStatusCode($statusCode)
    {
        $inferno = $this->mockInferno();

        $response = new \Requests_Response;
        $response->status_code = $statusCode;

        $inferno->shouldReceive('makeRequest')
                ->andReturn($response);

        return $inferno;
    }

    protected function mockInferno()
    {
        $class = \Models\Merchant\Webhook\Inferno::class;

        $inferno = Mockery::mock($class)->makePartial();

        $this->app->instance('webhook.inferno', $inferno);

        return $inferno;
    }
}
