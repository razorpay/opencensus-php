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

        $class = \Models\Merchant\Webhook\Inferno::class;

        $inferno = Mockery::mock($class.'[fire]');

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
}
