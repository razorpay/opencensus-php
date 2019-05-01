<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Status;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class TransactionTest extends TestCase
{
    public function testInitiatePay()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $coproto = $helper->initiatePay();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getId(), $transaction->payer->getId());
        $this->assertSame($this->fixtures->vpa(self::DEVICE_2)->getId(), $transaction->payee->getId());
    }

    public function testInitiateCollect()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $coproto = $helper->initiateCollect();

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getId(), $transaction->payee->getId());
        $this->assertSame($this->fixtures->vpa(self::DEVICE_2)->getId(), $transaction->payer->getId());
    }

    public function testPayAuthorize()
    {
        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $content = $this->handleSdkRequest($coproto);

        $helper->withSchemaValidated();

        $response = $helper->authorizeTransaction($coproto['callback'], $content);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isCompleted());
    }

    public function testCollectAuthorize()
    {
        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiateCollect();

        $content = $this->handleSdkRequest($coproto);

        $helper->withSchemaValidated();

        $response = $helper->authorizeTransaction($coproto['callback'], $content);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isProcessing());
    }

    public function testCollectAccept()
    {
        $this->forceTestMode();

        $helper = $this->getTransactionHelper();

        $this->mockSdk()->setCallback('COLLECT_REQUEST_RECEIVED', [
            Fields::AMOUNT                  => '1.00',
            Fields::PAYEE_VPA               => 'random@mypsp',
            Fields::PAYER_VPA               => $this->fixtures->vpa->getAddress(),
            Fields::UPI_REQUEST_ID          => 'RZP' . str_random(32),
            Fields::REMARKS                 => 'SomeTransaction',
            Fields::MERCHANT_CUSTOMER_ID    => $this->fixtures->deviceToken(self::DEVICE_1)
                                                              ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID]
        ]);

        $request = $this->mockSdk()->callback();

        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->getDbLastTransaction();

        $this->assertArraySubset([
            Entity::CUSTOMER_ID       => $this->fixtures->device->getCustomerId(),
            Entity::STATUS            => Status::CREATED,
            Entity::INTERNAL_STATUS   => Status::CREATED,
            Entity::PAYER_ID          => $this->fixtures->vpa->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
        ], $transaction->toArray());

        $coproto = $helper->initiateAuthorize($transaction->getPublicId());

        $content = $this->handleSdkRequest($coproto);

        $helper->withSchemaValidated();

        $helper->authorizeTransaction($coproto['callback'], $content);

        $this->assertArraySubset([
            Entity::CUSTOMER_ID       => $this->fixtures->device->getCustomerId(),
            Entity::STATUS            => Status::COMPLETED,
            Entity::INTERNAL_STATUS   => Status::COMPLETED,
            Entity::PAYER_ID          => $this->fixtures->vpa->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
        ], $transaction->reload()->toArray());
    }

    protected function forceTestMode()
    {
        $handle = clone $this->fixtures->handle;
        // Temporary work around, could not find better way
        $handle->setConnection('live')->setCode('000')->saveOrFail();
    }
}
