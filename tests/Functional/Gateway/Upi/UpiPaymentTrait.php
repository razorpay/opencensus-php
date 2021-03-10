<?php

namespace RZP\Tests\Functional\Gateway\Upi;

use RZP\Models\Payment\Entity;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;

trait UpiPaymentTrait
{

    public function testUpiCollectPaymentCreateSuccess()
    {
        $this->payment['description'] = 'collect_request_success_v2';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $this->assertEquals('async', $response['type']);

        $payment = $this->getDbLastPayment();

        $upiEntity = $this->getDbLastUpi();

        $this->assertArraySubset([
            Entity::STATUS          => 'created',
            Entity::GATEWAY         => $this->gateway,
            Entity::TERMINAL_ID     => $this->sharedTerminal->getId(),
            Entity::REFUND_AT       => null,
        ], $payment->toArray());

        $this->assertArraySubset([
            UpiEntity::PAYMENT_ID          => $payment->getId(),
            UpiEntity::TYPE                => 'collect',
            UpiEntity::ACTION              => 'authorize',
            UpiEntity::GATEWAY             => $this->gateway,
        ], $upiEntity->toArray());
    }

    public function testUpiCollectUpiPaymentCreateFail()
    {
        $this->payment['description'] = 'collect_request_failed_v2';

        $this->makeRequestAndCatchException(function ()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);
        });

        $payment = $this->getDbLastPayment();

        $upiEntity = $this->getDbLastUpi();

        $this->assertArraySubset([
            Entity::STATUS          => 'failed',
            Entity::GATEWAY         => $this->gateway,
            Entity::TERMINAL_ID     => $this->sharedTerminal->getId(),
            Entity::REFUND_AT       => null,
        ], $payment->toArray());

        $this->assertArraySubset([
            UpiEntity::PAYMENT_ID          => $payment->getId(),
            UpiEntity::TYPE                => 'collect',
            UpiEntity::ACTION              => 'authorize',
            UpiEntity::GATEWAY             => $this->gateway,
        ], $upiEntity->toArray());
    }
}

