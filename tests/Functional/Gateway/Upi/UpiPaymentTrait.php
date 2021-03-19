<?php

namespace RZP\Tests\Functional\Gateway\Upi;

use RZP\Models\Payment\Status;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;

trait UpiPaymentTrait
{

    public function testUpiCollectPaymentCreateSuccess($description = 'collect_request_success_v2')
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $payment['description'] = $description;

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

    public function testUpiIntentPaymentCreateSuccess($description = 'intent_request_success_v2')
    {
        $payment = $this->getDefaultUpiIntentPaymentArray();

        $intentTerminal = 'terminal:'.($this->gateway).'_intent_terminal';

        $this->sharedTerminal = $this->fixtures->create($intentTerminal);

        $payment['upi']['flow'] = 'intent';

        $payment['description'] = $description;

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

    public function testUpiCollectPaymentSuccess()
    {
        $this->testUpiCollectPaymentCreateSuccess();

        $payment = $this->getDbLastpayment();

        $upiEntity = $this->getDbLastUpi();

        $request = $this->mockServer()->getCallback($upiEntity->toArray(), $payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $upiEntity = $this->getDbLastUpi();

        $payment = $this->getDbLastPayment();

        // We should have received a successful response
        $this->assertEquals(['success' => true], $response);

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::REFERENCE16     => $upiEntity->getNpciReferenceId(),
            Entity::TERMINAL_ID     => $this->sharedTerminal->getId(),
            Entity::GATEWAY         => $this->gateway
        ], $payment->toArray());

        $this->assertArraySubset([
            UpiEntity::TYPE                 => Flow::COLLECT,
            UpiEntity::ACTION               => 'authorize',
            UpiEntity::GATEWAY              => $this->gateway,
            UpiEntity::VPA                  => 'vishnu@icici',
        ], $upiEntity->toArray());

        $this->assertNotNull($upiEntity->getStatusCode());
    }

    public function testUpiPaymentCallbackFailed()
    {
        $this->testUpiCollectPaymentCreateSuccess('callback_failed_v2');

        $payment = $this->getDbLastpayment();

        $upiEntity = $this->getDbLastUpi();

        $request = $this->mockServer()->getCallback($upiEntity->toArray(), $payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $upiEntity = $this->getDbLastUpi();

        $payment = $this->getDbLastPayment();

        // We should receive a failed response
        $this->assertEquals(['success' => false], $response);

        $this->assertEquals(Status::FAILED, $payment->getStatus());

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::ERROR_DESCRIPTION   => 'Payment failed. Please try again with another bank account.'
        ], $payment->toArray());

        $this->assertArraySubset([
            UpiEntity::TYPE          => Flow::COLLECT,
            UpiEntity::ACTION        => 'authorize',
            UpiEntity::GATEWAY       => $this->gateway,
        ], $upiEntity->toArray());

        $this->assertNotNull($upiEntity->getStatusCode());
    }

    public function testCallbackAmountMismatch()
    {
        $this->testUpiIntentPaymentCreateSuccess('callback_amount_mismatch_v2');

        $payment = $this->getDbLastpayment();

        $upiEntity = $this->getDbLastUpi();

        $request = $this->mockServer()->getCallback($upiEntity->toArray(), $payment->toArray());

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(['success' => false], $response);

        $payment->reload();

        $this->assertSame('failed', $payment->getStatus());

        $this->assertSame('SERVER_ERROR_AMOUNT_TAMPERED', $payment->getInternalErrorCode());
    }
}

