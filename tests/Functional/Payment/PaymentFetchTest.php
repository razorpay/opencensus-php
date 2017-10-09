<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentFetchTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentFetchTestData.php';

        parent::setUp();
    }

    public function testFetchRuleCascadingForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('payment');

        $this->startTest();
    }

    public function testFetchRulesForPrivateWithExtraFieldsError()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchRulesCascadingForProxyAuth()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->fixtures->create('payment',
            [
                'email' => $testData['request']['content']['email']
            ]);

        $this->startTest();
    }

    public function testFetchRulesWithSignedIdForPrivateAuth()
    {
        $this->ba->privateAuth();

        $order = $this->fixtures->create('order');

        $this->fixtures->create('payment', ['order_id' => $order->getId()]);

        $this->testData[__FUNCTION__]['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testFetchWithExpandsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['content']['email'] = $payment->getEmail();

        $content = $this->startTest();
    }

    public function testFindWithExpandsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testFetchWithExpandsForPrivateAuthWithInvalidExpand()
    {
        $this->ba->privateAuth();

        $payment = $this->fixtures->create('payment');

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

}
