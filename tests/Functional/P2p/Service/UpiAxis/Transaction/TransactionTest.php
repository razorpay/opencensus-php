<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Type;
use RZP\Models\P2p\Transaction\Flow;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Status;
use RZP\Tests\P2p\Service\Base\Traits;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Transaction\UpiTransaction;

class TransactionTest extends TestCase
{
    use Traits\TransactionTrait;

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
        $this->assertGreaterThanOrEqual($transaction->getCompletedAt(), $this->now()->getTimestamp());
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
        $this->assertGreaterThanOrEqual($transaction->getInitiatedAt(), $this->now()->getTimestamp());
    }

    public function testCollectAccept()
    {
        $helper = $this->getTransactionHelper();

        $expiry = (clone $this->testCurrentTime)->timezone('Asia/Kolkata')->addDay(1);
        $gatewayTransactionId = str_random(35);
        $this->mockSdk()->setCallback('COLLECT_REQUEST_RECEIVED', [
            Fields::AMOUNT                  => '1.00',
            Fields::PAYEE_VPA               => 'random@mypsp',
            Fields::PAYER_VPA               => $this->fixtures->vpa->getAddress(),
            Fields::UPI_REQUEST_ID          => 'RZP' . str_random(32),
            Fields::REMARKS                 => 'SomeTransaction',
            Fields::GATEWAY_TRANSACTION_ID  => $gatewayTransactionId,
            Fields::EXPIRY                  => $expiry->toIso8601String(),
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
            Entity::EXPIRE_AT         => $expiry->getTimestamp(),
        ], $transaction->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID => $gatewayTransactionId,
            UpiTransaction\Entity::GATEWAY_TRANSACTION_ID => $gatewayTransactionId,
        ], $transaction->upi->toArray());

        $coproto = $helper->initiateAuthorize($transaction->getPublicId());
        $this->assertSame($gatewayTransactionId, $coproto['request']['content']['upiRequestId']);

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

    public function testPayAccept()
    {
        $helper = $this->getTransactionHelper();

        $gatewayTransactionId = str_random(35);
        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_PAY', [
            Fields::AMOUNT                  => '1.00',
            Fields::PAYER_VPA               => 'random@mypsp',
            Fields::PAYEE_VPA               => $this->fixtures->vpa->getAddress(),
            Fields::UPI_REQUEST_ID          => 'RZP' . str_random(32),
            Fields::REMARKS                 => 'SomeTransaction',
            Fields::GATEWAY_TRANSACTION_ID  => $gatewayTransactionId,
            Fields::MERCHANT_CUSTOMER_ID    => $this->fixtures->deviceToken(self::DEVICE_1)
                                                   ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID]
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->getDbLastTransaction();

        $this->assertArraySubset([
            Entity::CUSTOMER_ID       => $this->fixtures->device->getCustomerId(),
            Entity::STATUS            => Status::COMPLETED,
            Entity::INTERNAL_STATUS   => Status::COMPLETED,
            Entity::PAYEE_ID          => $this->fixtures->vpa->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
        ], $transaction->reload()->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID => $gatewayTransactionId,
            UpiTransaction\Entity::GATEWAY_TRANSACTION_ID => $gatewayTransactionId,
        ], $transaction->upi->toArray());
    }

    public function testCollectReject()
    {
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

        $coproto = $helper->initiateReject($transaction->getPublicId());

        $content = $this->handleSdkRequest($coproto);

        $helper->withSchemaValidated();

        $helper->authorizeTransaction($coproto['callback'], $content);

        $this->assertArraySubset([
            Entity::CUSTOMER_ID       => $this->fixtures->device->getCustomerId(),
            Entity::STATUS            => Status::REJECTED,
            Entity::INTERNAL_STATUS   => Status::REJECTED,
            Entity::PAYER_ID          => $this->fixtures->vpa->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
        ], $transaction->reload()->toArray());
    }

    public function testCollectAccepted()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction([]);

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                  => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA               => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA               => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID          => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                 => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID     => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID    => $transaction->getCustomerId(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction->reload();

        $this->assertArraySubset([
            Entity::STATUS            => Status::COMPLETED,
            Entity::INTERNAL_STATUS   => Status::COMPLETED,
        ], $transaction->reload()->toArray());
    }

    public function testCollectPendingToSuccess()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectPendingTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                      => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                   => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                   => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID              => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                     => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID         => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID        => $transaction->getCustomerId(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $this->assertTrue($transaction->reload()->isCompleted());
        $this->assertArraySubset([
            Entity::STATUS            => Status::COMPLETED,
        ], $transaction->toArrayPublic());

        $this->assertArraySubset([
            UpiTransaction\Entity::GATEWAY_ERROR_CODE           => 'BT',
            UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION    => 'Transaction pending'
        ], $transaction->upi->toArrayPublic());
    }

    public function testCollectOnus()
    {
        $helper = $this->getTransactionHelper();

        $request = $helper->initiateCollect();

        $content = $this->handleSdkRequest($request);

        $helper->authorizeTransaction($request['callback'], $content);

        $transaction1 = $this->getDbLastTransaction();

        $this->assertArraySubset([
            Entity::CUSTOMER_ID       => $this->fixtures->device->getCustomerId(),
            Entity::STATUS            => Status::INITIATED,
            Entity::INTERNAL_STATUS   => Status::INITIATED,
            Entity::PAYER_ID          => $this->fixtures->vpa(self::DEVICE_2)->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
            Entity::TYPE              => Type::COLLECT,
            Entity::FLOW              => Flow::CREDIT,
        ], $transaction1->toArray());

        $this->mockSdk()->setCallback('COLLECT_REQUEST_RECEIVED', [
            Fields::AMOUNT                  => '1.00',
            Fields::PAYEE_VPA               => $this->fixtures->vpa(self::DEVICE_1)->getAddress(),
            Fields::PAYER_VPA               => $this->fixtures->vpa(self::DEVICE_2)->getAddress(),
            Fields::REMARKS                 => 'SomeTransaction',
            Fields::GATEWAY_TRANSACTION_ID  => $content['sdk']['gatewayTransactionId'],
            Fields::GATEWAY_REFERENCE_ID    => $content['sdk']['gatewayReferenceId'],
            Fields::MERCHANT_CUSTOMER_ID    => $this->fixtures->deviceToken(self::DEVICE_2)
                                                   ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID]
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction2 = $this->getDbLastTransaction();

        $this->assertArraySubset([
            Entity::CUSTOMER_ID       => $this->fixtures->device(self::DEVICE_2)->getCustomerId(),
            Entity::STATUS            => Status::CREATED,
            Entity::INTERNAL_STATUS   => Status::CREATED,
            Entity::PAYER_ID          => $this->fixtures->vpa(self::DEVICE_2)->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa(self::DEVICE_2)->getBankAccountId(),
            Entity::TYPE              => Type::COLLECT,
            Entity::FLOW              => Flow::DEBIT,
        ], $transaction2->toArray());

        $this->assertSame($transaction1->upi->getNetworkTransactionId(), $transaction2->upi->getNetworkTransactionId());
        $this->assertSame($transaction1->upi->getRrn(), $transaction2->upi->getRrn());

        $this->fixtures->switchDeviceSet(self::DEVICE_2);

        $coproto = $helper->initiateAuthorize($transaction2->getPublicId());

        $content = $this->handleSdkRequest($coproto);

        $helper->withSchemaValidated();

        $helper->authorizeTransaction($coproto['callback'], $content);

        $this->assertArraySubset([
            Entity::CUSTOMER_ID       => $this->fixtures->device->getCustomerId(),
            Entity::STATUS            => Status::COMPLETED,
            Entity::INTERNAL_STATUS   => Status::COMPLETED,
            Entity::PAYER_ID          => $this->fixtures->vpa->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
        ], $transaction2->reload()->toArray());
    }

    public function testCollectRejectWithBlock()
    {
        $transaction = $this->createCollectIncomingTransaction();

        $helper = $this->getTransactionHelper();

        $content = [
            'beneficiary' => [
                'username'  => $transaction->payee->getUsername(),
                'handle'    => $transaction->payee->getHandle(),
                'type'      => $transaction->payee->getP2pEntityName(),
                'blocked'   => true,
                'spammed'   => false,
            ],
        ];

        $this->mockActionRequestFunction(['vpaHandleBeneficiary' => function($content) use ($transaction)
        {
            $this->assertSame($transaction->upi->getNetworkTransactionId(), $content['upiRequestId']);
            $this->assertSame('true', $content['shouldBlock']);
            $this->assertSame('false', $content['shouldSpam']);
        }]);

        $helper->initiateReject($transaction->getPublicId(), $content);
    }

    public function testCollectRejectWithBlockAndSpam()
    {
        $transaction = $this->createCollectIncomingTransaction();

        $helper = $this->getTransactionHelper();

        $content = [
            'beneficiary' => [
                'username'  => $transaction->payee->getUsername(),
                'handle'    => $transaction->payee->getHandle(),
                'type'      => $transaction->payee->getP2pEntityName(),
                'blocked'   => true,
                'spammed'   => true,
            ],
        ];

        $this->mockActionRequestFunction(['vpaHandleBeneficiary' => function($content) use ($transaction)
        {
            $this->assertSame($transaction->upi->getNetworkTransactionId(), $content['upiRequestId']);
            $this->assertSame('true', $content['shouldBlock']);
            $this->assertSame('true', $content['shouldSpam']);
        }]);

        $helper->initiateReject($transaction->getPublicId(), $content);
    }
}
