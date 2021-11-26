<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Models\Payment;
use RZP\Models\PaymentLink;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class ServiceTest extends BaseTest
{
    use PaymentTrait;
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';

    protected $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PaymentLink\Service::class);;
    }

    public function testAppendAmountIfPossible()
    {
        $data       = $this->createPaymentLinkAndOrderForThat();
        $pl         = $data['payment_link'];
        $order      = $data['payment_link_order']['order'];
        $payment    = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $order);
        $payload = [];
        $this->service->appendAmountIfPossible($pl->getPublicId(), [
            'razorpay_payment_id'   => $payment[Payment\Entity::ID]
        ], $payload);
        $this->assertEquals(15000, array_get($payload, PaymentLink\Entity::REQUEST_PARAMS.'.'.PaymentLink\Entity::AMOUNT));
    }
}
