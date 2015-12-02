<?php

namespace Tests\Functional\Merchant;

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

    public function testEditWebhook()
    {
        $input = array(
            'url' => 'http://random.com',
            'events' => [
                'payment.authorized' => '1',
            ]);

        $webhook = $this->createWebhook($input);

        $this->testData[__FUNCTION__]['request']['url'] = '/webhooks/'.$webhook['id'];

        $this->startTest();
    }
}
