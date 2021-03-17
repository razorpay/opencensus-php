<?php

namespace RZP\Tests\Functional\Gateway\Upi;

use RZP\Models\Payment\Entity;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;

trait UpiPaymentTrait
{

    public function testUpiCollectPaymentCreateSuccess()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['description'] = 'collect_request_success_v2';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

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
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['description'] = 'collect_request_failed_v2';

        $this->makeRequestAndCatchException(function () use ($payment)
        {
            $this->doAuthPaymentViaAjaxRoute($payment);
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

    public function testUpiIntentPaymentCreateSuccess()
    {
        $payment = $this->getDefaultUpiIntentPaymentArray();

        $intentTerminal = 'terminal:'.($this->gateway).'_intent_terminal';

        $this->sharedTerminal = $this->fixtures->create($intentTerminal);

        $payment['upi']['flow'] = 'intent';

        $payment['description'] = 'intent_request_success_v2';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

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
            UpiEntity::TYPE                => 'pay',
            UpiEntity::ACTION              => 'authorize',
            UpiEntity::GATEWAY             => $this->gateway,
        ], $upiEntity->toArray());
    }
}

