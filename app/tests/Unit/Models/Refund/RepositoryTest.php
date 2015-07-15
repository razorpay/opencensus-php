<?php
namespace Tests\Unit\Models\Refund;

use Tests\Functional\Fixtures\Entity\Refund as RefundFixture;
use Tests\Functional\Fixtures\Entity\Payment as PaymentFixture;
use Tests\Functional\Fixtures\Entity\Merchant as MerchantFixture;
use Tests\Functional\TestCase;
use Models\Payment\Refund\Repository as RefundRepository;

class RepositoryTest extends TestCase
{
    function setUp()
    {
        parent::setUp();
        $this->paymentFixture = (new PaymentFixture);
        $this->refundFixture = (new RefundFixture);
        $this->merchantId = '10000000000000';

        $this->startTime = time();

        for ($i=0; $i < 3; $i++) {
            $payment = ['payment' => $this->paymentFixture->createCardCaptured(['merchant_id'=>$this->merchantId])];
            $refund = $this->refundFixture->createFromPayment($payment);
        }

        $this->endTime = time();
    }

    function testFetchBetweenTimestampsForMerchant()
    {
        $refunds = (new RefundRepository)
            ->fetchBetweenTimestampsForMerchant($this->startTime, $this->endTime, $this->merchantId);
        $this->assertEquals(3, count($refunds));
    }
}
