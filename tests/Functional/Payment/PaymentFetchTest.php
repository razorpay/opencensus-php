<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * Covers Base/Fetch implementation. Currently it's not enabled for Payment
 * model but this asserts that existing flow is working fine as well. Later
 * when Payment model is enabled for new flow this will be still there.
 *
 */
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

    public function testFetchRuleVPAFilterForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('payment', [
            'method' => 'upi',
            'vpa'    => 'success1@razorpay',
        ]);

        $this->fixtures->create('payment', [
            'method' => 'upi',
            'vpa'    => 'success2@razorpay',
        ]);

        $this->startTest();
    }

    public function testFetchCardQueryParams()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('payment', [
            'card_id' => '100000001lcard'
        ]);

        $this->startTest();
    }

    public function testFetchRulesForPrivateWithExtraFieldsError()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchRuleswithCustomerIdError()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchRulesCascadingForProxyAuth()
    {
        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $this->fixtures->create(
            'payment',
            [
                'email' => $testData['request']['content']['email'],
            ]);

        $this->startTest();
    }

    public function testFetchRulesWithSignedIdForPrivateAuth()
    {
        $this->ba->privateAuth();

        $order = $this->fixtures->create('order');

        $this->fixtures->create('payment', ['order_id' => $order->getId()]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->startTest();
    }

    public function testFetchWithExpandsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email'] = $payment->getEmail();

        $this->startTest();
    }

    public function testFindWithExpandsForPrivateAuth()
    {
        $this->ba->privateAuth();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $payment = $this->fixtures->create('payment', ['card_id' => $card->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testFetchWithExpandsTransfer()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFindWithExpandsForPrivateAuthWithInvalidExpand()
    {
        $this->ba->privateAuth();

        $payment = $this->fixtures->create('payment');

        $this->testData[__FUNCTION__]['request']['url'] .= $payment->getPublicId();

        $this->startTest();
    }

    public function testFetchWithDisputes()
    {
        $this->ba->proxyAuth();

        $payment = $this->fixtures
                        ->create(
                            'payment:captured',
                            [
                                'disputed'  => 1,
                                'fee'       => 0,
                                'email'     => 'abc@email.com',
                            ]);

        $this->fixtures->times(2)->create('dispute', ['payment_id' => $payment->getId()]);

        $this->startTest();
    }

    public function testFetchPaymentByRecurringFilter()
    {
        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockTokenex();

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $payment = $this->getDefaultRecurringPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $this->ba->proxyAuth();

        $this->startTest();
    }
}
