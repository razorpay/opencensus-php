<?php

namespace RZP\Tests\Functional\Request;

use RZP\Models\Key;
use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Request\Traits\KeylessPublicAuthTrait;

/**
 * Functional tests to assert working of keyless auth layer on public route.
 */
class KeylessPublicAuthTest extends TestCase
{
    use PaymentTrait, KeylessPublicAuthTrait
    {
        KeylessPublicAuthTrait::createOrder insteadof PaymentTrait;
    }

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/KeylessPublicAuthTestData.php';

        parent::setUp();

        // For all keyless auth tests, the test merchant should not have any keys, so marking the
        // keys created by test fixtures expired.
        $this->fixtures->key->edit('TheTestAuthKey', ['expired_at' => time()]);

        // Also activates the merchant so he is able to make live requests for the tests.
        $this->fixtures->merchant->activate();
    }

    public function testGetInvoiceStatus()
    {
        $this->createInvoice();

        $this->startTest();
    }

    public function testGetInvoiceStatusOfInvalidId1()
    {
        $this->createInvoice();

        $this->startTest();
    }

    public function testGetInvoiceStatusOfInvalidId2()
    {
        $this->createInvoice(Mode::TEST);

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithNoXEntityId()
    {
        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithXEntityIdInQuery()
    {
        $this->createOrder();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithOrderIdInQuery()
    {
        $this->createOrder();

        $this->startTest();
    }

    public function testGetCheckoutPreferencesWithInvoiceIdInQuery()
    {
        $this->createInvoice();

        $this->startTest();
    }

    public function testPaymentCreateWithXEntityIdInInputWhenKeyExists()
    {
        $this->fixtures->key->edit('TheTestAuthKey', ['expired_at' => null]);

        $order = $this->createOrder(Mode::TEST);

        $request = $this->buildAuthPaymentRequest();
        $request['content']['order_id'] = 'order_100000000order';
        $request['content']['amount'] = $order->getAmount();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('order_100000000order', $response['razorpay_order_id']);
        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertCount(3, $response);
    }

    public function testPaymentCreateWithOrderIdInInput()
    {
        $order = $this->createOrder(Mode::TEST);

        $request = $this->buildAuthPaymentRequest();

        $request['content']['order_id'] = 'order_100000000order';
        $request['content']['amount']   = $order->getAmount();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('order_100000000order', $response['razorpay_order_id']);
        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertCount(2, $response);
    }

    /**
     * When key exists and payment create request is made against order, in response we must also include the signature.
     */
    public function testPaymentCreateWithOrderIdInInputWhenKeyExists()
    {
        $this->fixtures->key->edit('TheTestAuthKey', ['expired_at' => null]);

        $order = $this->createOrder(Mode::TEST);

        $request = $this->buildAuthPaymentRequest();

        $request['content']['order_id'] = 'order_100000000order';
        $request['content']['amount']   = $order->getAmount();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('order_100000000order', $response['razorpay_order_id']);
        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertCount(3, $response);
    }

    public function testPaymentCreateWithInvoicesOrderIdInInput()
    {
        $invoice = $this->createInvoice(Mode::TEST);

        $request = $this->buildAuthPaymentRequest();

        $request['content']['order_id'] = 'order_100000invorder';
        $request['content']['amount']   = $invoice->getAmount();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('inv_1000000invoice', $response['razorpay_invoice_id']);
        $this->assertEquals('paid', $response['razorpay_invoice_status']);
        $this->assertEquals(null, $response['razorpay_invoice_receipt']);
        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertCount(4, $response);
    }

    /**
     * When key exists and payment create request is made against invoice, in response we must also include the signature.
     */
    public function testPaymentCreateWithInvoicesOrderIdInInputWhenKeyExists()
    {
        $this->fixtures->key->edit('TheTestAuthKey', ['expired_at' => null]);

        $invoice = $this->createInvoice(Mode::TEST);

        $request = $this->buildAuthPaymentRequest();

        $request['content']['order_id'] = 'order_100000invorder';
        $request['content']['amount']   = $invoice->getAmount();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('inv_1000000invoice', $response['razorpay_invoice_id']);
        $this->assertEquals('paid', $response['razorpay_invoice_status']);
        $this->assertEquals(null, $response['razorpay_invoice_receipt']);
        $this->assertArrayHasKey('razorpay_payment_id', $response);
        $this->assertArrayHasKey('razorpay_signature', $response);
        $this->assertCount(5, $response);
    }
}
