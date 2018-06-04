<?php

namespace RZP\Tests\Functional\PaymentLink;

use RZP\Models\PaymentLink;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PaymentLinkTest extends TestCase
{
    use RequestResponseFlowTrait;

    const DEFAULT_PAYMENT_LINK_ID = '100000000000pl';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaymentLinkTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreatePaymentLink()
    {
        $this->startTest();
    }

    public function testCreatePaymentLinkWithBadExpireBy()
    {
        $this->startTest();
    }

    public function testFetchPaymentLink()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testFetchPaymentLinks()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testUpdatePaymentLink()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testUpdatePaymentLinkWithBadExpireBy()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testPaymentLinkSendNotification()
    {
        $this->createPaymentLink();

        $this->startTest();
    }

    public function testInactivePaymentLinkSendNotification()
    {
        $attributes = [
            PaymentLink\Entity::STATUS        => PaymentLink\Status::INACTIVE,
            PaymentLink\Entity::STATUS_REASON => PaymentLink\StatusReason::EXPIRED,
            PaymentLink\Entity::EXPIRE_BY     => 1400000000,
        ];

        $this->createPaymentLink(self::DEFAULT_PAYMENT_LINK_ID, $attributes);

        $this->startTest();
    }

    // -------------------- Protected methods --------------------

    protected function createPaymentLink(string $id = self::DEFAULT_PAYMENT_LINK_ID, array $attributes = [])
    {
        $attributes[PaymentLink\Entity::ID] = $id;

        $this->fixtures->create('payment_link', $attributes);
    }
}
