<?php
namespace Tests\Unit\Models\Payment;

use Tests\Functional\Fixtures\Entity\Payment as PaymentFixture;
use Tests\Functional\Fixtures\Entity\Merchant as MerchantFixture;
use Tests\Functional\TestCase;
use Models\Payment\Repository as PaymentRepository;

class RepositoryTest extends TestCase
{
    function setUp()
    {
        parent::setUp();
        $this->paymentFixture = (new PaymentFixture);
        $this->merchantId = '10000000000000';

        $this->startTime = time();

        for ($i=0; $i < 3; $i++) {
            $this->paymentFixture->createCardCaptured(['merchant_id'=>$this->merchantId]);
        }

        for ($i=0; $i < 3; $i++) {
            $payment = $this->paymentFixture->createAuthorized(['merchant_id'=>$this->merchantId]);
        }

        $this->endTime = time();
    }

    function testCapturedSummary()
    {
        $totalAmount = (new PaymentRepository)
            ->fetchCapturedBetweenTimestamp($this->startTime, $this->endTime, $this->merchantId);
        $this->assertEquals(3, count($totalAmount));
    }

    function testAuthorizedList()
    {
        $payments = (new PaymentRepository)
            ->fetchAuthorizedBetweenTimestamp($this->startTime, $this->endTime, $this->merchantId);
        $this->assertEquals(3, count($payments));
    }
}
