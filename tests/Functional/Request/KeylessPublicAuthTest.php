<?php

namespace RZP\Tests\Functional\Request;

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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/KeylessPublicAuthTestData.php';

        parent::setUp();
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

    // TODO: After discussion about signature.

    public function testPaymentCreateWithXEntityIdInInput()
    {
        // $order = $this->createOrder(Mode::TEST);

        // $request = $this->buildAuthPaymentRequest();
        // $request['content']['order_id'] = 'order_100000000order';
        // $request['content']['amount'] = $order->getAmount();

        // $response = $this->makeRequestAndGetContent($request);
    }

    public function testPaymentCreateWithOrderIdInQuery()
    {
    }

    public function testPaymentCreateWithInvoiceIdInQuery()
    {
    }
}
