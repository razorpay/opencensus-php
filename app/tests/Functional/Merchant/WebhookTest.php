<?php

namespace Tests\Functional\Merchant;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class WebhookTest extends TestCase
{
    use PaymentTrait;

    protected $webhookData = [
        'url' => 'http://random.com',
        'events' => [
            'payment.authorized' => '1',
        ]
    ];

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

    public function testEditWebhook()
    {
        $webhook = $this->createWebhook($this->webhookData);

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->startTest();
    }

    public function testGetWebhooks()
    {
        $this->createWebhook($this->webhookData);

        $this->startTest();
    }

    public function testCreateWebhookWrongUrl()
    {
        $data = $this->startTest();
   }
}
