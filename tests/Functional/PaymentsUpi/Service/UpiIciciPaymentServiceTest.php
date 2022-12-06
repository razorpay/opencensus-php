<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;

class UpiIciciPaymentServiceTest extends UpiPaymentServiceTest
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testPaymentSuccessWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(0, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer('upi_icici')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::REFERENCE16     => $upiEntity['npci_reference_id'],
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway
        ], $payment);
    }

    public function testPaymentFailureWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(0, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastpayment()->toArray();

        $upiEntity = $this->getDbLastUpi();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => 0,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE          => Flow::COLLECT,
            UpiEntity::ACTION        => 'authorize',
            UpiEntity::GATEWAY       => $this->gateway,
            UpiEntity::STATUS_CODE   => 'U30'
        ], $upiEntity->toArray());
    }
}
