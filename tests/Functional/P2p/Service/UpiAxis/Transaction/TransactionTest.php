<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Mode;
use RZP\Models\P2p\Transaction\Type;
use RZP\Models\P2p\Transaction\Flow;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Status;
use RZP\Tests\P2p\Service\Base\Traits;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Models\P2p\Base\Metrics\TransactionMetric;
use RZP\Tests\P2p\Service\Base\Traits\MetricsTrait;

class TransactionTest extends TestCase
{
    use MetricsTrait;
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
            Entity::STATUS            => Status::REQUESTED,
            Entity::INTERNAL_STATUS   => Status::REQUESTED,
            Entity::PAYER_ID          => $this->fixtures->vpa->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
            Entity::EXPIRE_AT         => $expiry->getTimestamp(),
        ], $transaction->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID => $gatewayTransactionId,
            UpiTransaction\Entity::GATEWAY_TRANSACTION_ID => $gatewayTransactionId,
            UpiTransaction\Entity::MCC                    => 2222,
            UpiTransaction\Entity::REF_URL                => 'https::example.com',
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

    public function testCollectRequestAboveInitiateLimit()
    {
        $helper = $this->getTransactionHelper();

        $expiry = (clone $this->testCurrentTime)->timezone('Asia/Kolkata')->addDay(1);

        $gatewayTransactionId = str_random(35);

        $this->mockSdk()->setCallback('COLLECT_REQUEST_RECEIVED', [
            Fields::AMOUNT                  => '4000.00', // Amount above 2000 for incoming collect
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
            Entity::STATUS            => Status::REQUESTED,
            Entity::INTERNAL_STATUS   => Status::REQUESTED,
            Entity::PAYER_ID          => $this->fixtures->vpa->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
            Entity::EXPIRE_AT         => $expiry->getTimestamp(),
        ], $transaction->toArray());
    }

    public function testInitiateCollectWithPerDayLimitSuccess()
    {
        $helper = $this->getTransactionHelper();

        $allowedCollectRequestsPerDay = 5;

        // initiating allowed number of collect requests
        for ($i = 0; $i < $allowedCollectRequestsPerDay; $i++)
        {
            $helper->initiateCollect();
        }

        // Asserting allowed number of collect requests initiated successfully
        $this->assertSame($allowedCollectRequestsPerDay, $this->fixtures->getDbTransactions([])->count());

        $this->withFailureResponse($helper, function ($error)
        {
            $this->assertArraySubset([
                'code'          => 'BAD_REQUEST_ERROR',
            ], $error);
        });

        $helper->initiateCollect();
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
            UpiTransaction\Entity::MCC                    => 2222,
            UpiTransaction\Entity::REF_URL                => 'https::example.com',
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
            Entity::STATUS            => Status::REQUESTED,
            Entity::INTERNAL_STATUS   => Status::REQUESTED,
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

        $this->assertArraySubset([
            UpiTransaction\Entity::MCC        => 2222,
            UpiTransaction\Entity::REF_URL    => 'https::example.com',
        ], $transaction->upi->reload()->toArray());
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
            Fields::GATEWAY_TRANSACTION_ID  => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction->reload();

        $this->assertArraySubset([
            Entity::STATUS            => Status::COMPLETED,
            Entity::INTERNAL_STATUS   => Status::COMPLETED,
        ], $transaction->reload()->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::MCC      => 2222,
            UpiTransaction\Entity::REF_URL  => 'https::example.com',
        ], $transaction->upi->reload()->toArray());
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
            Fields::GATEWAY_TRANSACTION_ID      => $transaction->upi->getNetworkTransactionId(),
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

        $this->assertArraySubset([
            UpiTransaction\Entity::MCC      => 2222,
            UpiTransaction\Entity::REF_URL  => 'https::example.com',
        ], $transaction->upi->reload()->toArray());
    }

    public function testPayPendingToSuccess()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createPayIncomingTransaction([
            Entity::STATUS           => Status::PENDING,
            Entity::INTERNAL_STATUS  => Status::PENDING,
        ], [
            UpiTransaction\Entity::GATEWAY_ERROR_CODE           => 'BT',
            UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION    => 'Transaction pending'
        ]);

        // To test the case if context vpa is deleted
        $transaction->payee->deleteOrFail();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_PAY', [
            Fields::AMOUNT                      => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                   => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                   => $transaction->payee->getAddress(),
            Fields::GATEWAY_TRANSACTION_ID      => $transaction->upi->getNetworkTransactionId(),
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

        $this->assertArraySubset([
            UpiTransaction\Entity::MCC      => 2222,
            UpiTransaction\Entity::REF_URL  => 'https::example.com',
        ], $transaction->upi->reload()->toArray());
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
            Entity::STATUS            => Status::REQUESTED,
            Entity::INTERNAL_STATUS   => Status::REQUESTED,
            Entity::PAYER_ID          => $this->fixtures->vpa(self::DEVICE_2)->getId(),
            Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa(self::DEVICE_2)->getBankAccountId(),
            Entity::TYPE              => Type::COLLECT,
            Entity::FLOW              => Flow::DEBIT,
        ], $transaction2->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::MCC        => 2222,
            UpiTransaction\Entity::REF_URL    => 'https::example.com',
        ], $transaction2->upi->toArray());

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

    public function testInitiatePayIntent()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $upi = [
            'mcc'       => '0000',
            'ref_url'   => 'https::example.com',
            'ref_id'    => 'XrefId'
        ];

        $coproto = $helper->initiatePay([
            'mode'  => 'intent',
            'upi'   => $upi,
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Mode::INTENT, $transaction->getMode());
        $this->assertSame($upi['mcc'], $transaction->upi->getMcc());
        $this->assertSame($upi['ref_url'], $transaction->upi->getRefUrl());
        $this->assertSame($upi['ref_id'], $transaction->upi->getRefId());

        $content = $coproto['request']['content'];

        $this->assertSame($upi['mcc'], $content['mcc']);
        $this->assertSame($upi['ref_url'], $content['refUrl']);
        $this->assertSame($upi['ref_id'], $content['transactionReference']);
    }

    public function testInitiatePayQrCode()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $upi = [
            'mcc'       => '0000',
            'ref_url'   => 'https::example.com',
            'ref_id'    => 'XrefId'
        ];

        $coproto = $helper->initiatePay([
            'mode'  => 'qr_code',
            'upi'   => $upi,
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Mode::QR_CODE, $transaction->getMode());
        $this->assertSame($upi['mcc'], $transaction->upi->getMcc());
        $this->assertSame($upi['ref_url'], $transaction->upi->getRefUrl());
        $this->assertSame($upi['ref_id'], $transaction->upi->getRefId());

        $content = $coproto['request']['content'];

        $this->assertSame($upi['mcc'], $content['mcc']);
        $this->assertSame($upi['ref_url'], $content['refUrl']);
        $this->assertSame($upi['ref_id'], $content['transactionReference']);
    }

    public function testPayAuthorizeCallback()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createPayTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_DEBITED_VIA_PAY', [
            Fields::AMOUNT                      => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                   => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                   => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID              => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                     => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID         => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID        => $transaction->getCustomerId(),
            Fields::GATEWAY_TRANSACTION_ID      => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isCompleted());
        $this->assertArraySubset([
            Entity::STATUS            => Status::COMPLETED,
            Entity::INTERNAL_STATUS   => Status::COMPLETED,
        ], $transaction->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::GATEWAY_ERROR_CODE           => '00',
            UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION    => 'Your transaction is approved',
            UpiTransaction\Entity::MCC                          => 2222,
            UpiTransaction\Entity::REF_URL                      => 'https::example.com',
        ], $transaction->upi->toArrayPublic());
    }

    public function testCollectAuthorizeCallback()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectIncomingTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_DEBITED_VIA_COLLECT', [
            Fields::AMOUNT                      => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                   => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                   => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID              => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                     => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID         => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID        => $transaction->getCustomerId(),
            Fields::GATEWAY_TRANSACTION_ID      => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isCompleted());
        $this->assertArraySubset([
            Entity::STATUS            => Status::COMPLETED,
            Entity::INTERNAL_STATUS   => Status::COMPLETED,
        ], $transaction->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::GATEWAY_ERROR_CODE           => '00',
            UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION    => 'Your transaction is approved',
            UpiTransaction\Entity::MCC                          => 2222,
            UpiTransaction\Entity::REF_URL                      => 'https::example.com',
        ], $transaction->upi->toArrayPublic());
    }

    public function testPayCompletedCallback()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createPayTransaction([
            Entity::STATUS              => Status::COMPLETED,
            Entity::INTERNAL_STATUS     => Status::COMPLETED,
        ]);

        $this->mockSdk()->setCallback('CUSTOMER_DEBITED_VIA_PAY', [
            Fields::AMOUNT                      => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                   => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                   => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID              => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                     => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID         => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID        => $transaction->getCustomerId(),
            Fields::GATEWAY_TRANSACTION_ID      => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);
    }

    public function testFetchAll()
    {
        $transaction1 = $this->createPayTransaction([
            Entity::STATUS              => Status::PENDING,
            Entity::INTERNAL_STATUS     => Status::PENDING,
        ]);

        $this->createPayTransaction([
            Entity::STATUS              => Status::CREATED,
            Entity::INTERNAL_STATUS     => Status::CREATED
        ]);

        $transaction2 = $this->createCollectIncomingTransaction([]);

        $helper = $this->getTransactionHelper();

        $concern = $helper->raiseConcern($transaction1->getPublicId());

        $this->createPayTransaction();

        $this->createPayIncomingTransaction([
            Entity::STATUS              => Status::FAILED,
            Entity::INTERNAL_STATUS     => Status::FAILED,
        ]);

        $this->createPayIncomingTransaction([
            Entity::STATUS              => Status::FAILED,
            Entity::INTERNAL_STATUS     => Status::FAILED,
        ]);

        $transaction3 = $this->createPayTransaction([
            Entity::STATUS              => Status::COMPLETED,
            Entity::STATUS              => Status::COMPLETED
        ]);

        $collection = $helper->fetchAll([
            'expand'    => ['payer', 'payee', 'upi', 'concern'],
            'response'  => 'history',
        ]);

        $this->createCollectTransaction([
            Entity::FLOW                => Flow::CREDIT,
            Entity::STATUS              => Status::CREATED
        ]);

        $this->assertCollection($collection, 3, [
            [
                'id'        => $transaction3->getPublicId(),
                'status'    => 'completed',
                'type'      => 'pay'
            ],
            [
                'id'        => $transaction2->getPublicId(),
                'status'    => 'requested',
                'type'      => 'collect',
            ],
            [
                'id'        => $transaction1->getPublicId(),
                'status'    => 'pending',
                'type'      => 'pay',
            ]
        ]);

        $this->assertNotEmpty($collection['items'][2]['concern']);
    }

    public function testFetchAllPending()
    {
        $this->createPayTransaction();
        $this->createPayTransaction([
            Entity::STATUS  => Status::COMPLETED,
        ]);

        $this->createCollectIncomingTransaction();
        $this->createCollectIncomingTransaction([
            Entity::EXPIRE_AT   => $this->now()->subSecond()->getTimestamp(),
        ]);
        $this->createCollectIncomingTransaction([
            Entity::STATUS  => Status::COMPLETED,
        ]);
        $this->createCollectIncomingTransaction([
            Entity::STATUS  => Status::INITIATED,
        ]);

        $helper = $this->getTransactionHelper();

        $collection = $helper->fetchAll([
            'response'  => 'pending',
            'expand'    => ['payer', 'payee'],
        ]);

        $this->assertCollection($collection, 1, [
            [
                'status'    => 'requested',
                'type'      => 'collect',
                'flow'      => 'debit',
            ],
        ]);

        $payee = $collection['items'][0]['payee'];

        $this->assertTrue($payee['verified']);
    }

    public function testFetchDeletedBeneficiary()
    {
        $this->createPayTransaction([
            Entity::STATUS              => Status::COMPLETED,
            Entity::INTERNAL_STATUS     => Status::COMPLETED,
        ]);

        $this->fixtures->vpa(self::DEVICE_2)->delete();

        $helper = $this->getTransactionHelper();

        $transactions = $helper->fetchAll(['expand' => ['payer', 'payee']]);

        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
                          $transactions['items'][0]['payer']['id']);
        $this->assertSame($this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
                          $transactions['items'][0]['payee']['id']);
        $this->assertTrue($transactions['items'][0]['payee']['validated']);

        $this->createPayTransaction([
            Entity::PAYEE_TYPE          => 'bank_account',
            Entity::PAYEE_ID            => $this->fixtures->bankAccount(self::DEVICE_2)->getId(),
            Entity::STATUS              => Status::COMPLETED,
            Entity::INTERNAL_STATUS     => Status::COMPLETED,
        ]);

        $this->fixtures->bankAccount(self::DEVICE_2)->delete();

        $transactions = $helper->fetchAll(['expand' => ['payer', 'payee']]);

        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
                          $transactions['items'][0]['payer']['id']);
        $this->assertSame($this->fixtures->bankAccount(self::DEVICE_2)->getPublicId(),
                          $transactions['items'][0]['payee']['id']);
        $this->assertTrue($transactions['items'][0]['payee']['validated']);

        $this->createPayIncomingTransaction();

        $transactions = $helper->fetchAll(['expand' => ['payer', 'payee']]);

        $this->assertSame($this->fixtures->vpa(self::DEVICE_2)->getPublicId(),
                          $transactions['items'][0]['payer']['id']);
        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getPublicId(),
                          $transactions['items'][0]['payee']['id']);
        $this->assertTrue($transactions['items'][0]['payer']['validated']);
    }

    public function testDynamicInitiatePay()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $coproto = $helper->initiatePay([
            'payee' => [
                'id'                => null,
                'type'              => 'vpa',
                'username'          => 'test',
                'handle'            => 'mypsp',
                'beneficiary_name'  => 'Some Merchant',
            ]
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();
        $vpa         = $this->fixtures->getDbLastVpa();

        $this->assertTrue($vpa->isBeneficiary());
        $this->assertSame('test@mypsp', $vpa->getAddress());

        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getId(), $transaction->payer->getId());
        $this->assertSame($vpa->getId(), $transaction->payee->getId());

        $this->assertArraySubset([
            'payeeVpa'  => 'test@mypsp',
            'payeeName' => 'Some Merchant',
        ], $coproto['request']['content']);
    }

    public function testDynamicInitiateCollect()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $coproto = $helper->initiateCollect([
            'payer' => [
                'id'                => null,
                'type'              => 'vpa',
                'username'          => 'test',
                'handle'            => 'mypsp',
                'beneficiary_name'  => 'Some Merchant',
            ]
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();
        $vpa         = $this->fixtures->getDbLastVpa();

        $this->assertTrue($vpa->isBeneficiary());
        $this->assertSame('test@mypsp', $vpa->getAddress());

        $this->assertSame($vpa->getId(), $transaction->payer->getId());
        $this->assertSame($this->fixtures->vpa(self::DEVICE_1)->getId(), $transaction->payee->getId());

        $this->assertArraySubset([
            'payerVpa'  => 'test@mypsp',
            'payerName' => 'Some Merchant',
        ], $coproto['request']['content']);
    }


    public function testInitiatePayIntentToMerchant()
    {
        $helper = $this->getTransactionHelper();

        $upi = [
            'mcc'       => '1208',
            'ref_url'   => 'https::example.com',
            'ref_id'    => 'fourteenchardg'
        ];

        $coproto = $helper->initiatePay([
            'mode'  => 'intent',
            'upi'   => $upi,
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Mode::INTENT, $transaction->getMode());
        $this->assertSame($upi['mcc'], $transaction->upi->getMcc());
        $this->assertSame($upi['ref_url'], $transaction->upi->getRefUrl());
        $this->assertSame($upi['ref_id'], $transaction->upi->getRefId());

        $this->assertSame('PAY', $coproto['request']['action']);

        $content = $coproto['request']['content'];

        $customerHandle = explode('@', $content['customerVpa'])[1];
        $merchantHandle = explode('@', $content['merchantVpa'])[1];
        $this->assertSame($customerHandle, $merchantHandle);

        $this->mockSdk()->setCallback('CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY', [
            Fields::AMOUNT                      => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                   => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                   => $transaction->payee->getAddress(),
            Fields::GATEWAY_TRANSACTION_ID      => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                     => $transaction->getDescription(),
            Fields::MERCHANT_CUSTOMER_ID        => $transaction->getCustomerId(),
            Fields::MERCHANT_REQUEST_ID         => $transaction->upi->getRefId(),
            Fields::PAYEE_MCC                   => $transaction->upi->getMcc(),
            Fields::REF_URL                     => $transaction->upi->getRefUrl(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);
    }

    public function testInitiatePayQrCodeMerchant()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $upi = [
            'mcc'       => '1010',
            'ref_url'   => 'https::example.com',
            'ref_id'    => 'XrefId'
        ];

        $coproto = $helper->initiatePay([
            'mode'  => 'qr_code',
            'upi'   => $upi,
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Mode::QR_CODE, $transaction->getMode());
        $this->assertSame($upi['mcc'], $transaction->upi->getMcc());
        $this->assertSame($upi['ref_url'], $transaction->upi->getRefUrl());
        $this->assertSame($upi['ref_id'], $transaction->upi->getRefId());

        $this->assertSame('PAY', $coproto['request']['action']);

        $content = $coproto['request']['content'];

        $customerHandle = explode('@', $content['customerVpa'])[1];
        $merchantHandle = explode('@', $content['merchantVpa'])[1];
        $this->assertSame($customerHandle, $merchantHandle);
    }

    public function testInitiatePayIntentToOtherMerchant()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $upi = [
            'mcc'       => '1208',
            'ref_url'   => 'https::example.com',
            'ref_id'    => 'XrefId',

        ];

        $coproto = $helper->initiatePay([
            'mode'  => 'intent',
            'upi'   => $upi,
            'payee' => [
                'id'                => null,
                'username'          => 'some',
                'handle'            => 'mybank',
                'type'              => 'vpa',
                'beneficiary_name'  => 'benef_name',
            ]
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Mode::INTENT, $transaction->getMode());
        $this->assertSame($upi['mcc'], $transaction->upi->getMcc());
        $this->assertSame($upi['ref_url'], $transaction->upi->getRefUrl());
        $this->assertSame($upi['ref_id'], $transaction->upi->getRefId());

        $this->assertSame('SEND_MONEY', $coproto['request']['action']);

        $content = $coproto['request']['content'];

        $content = $coproto['request']['content'];

        $this->assertSame($upi['mcc'], $content['mcc']);
        $this->assertSame($upi['ref_url'], $content['refUrl']);
        $this->assertSame($upi['ref_id'], $content['transactionReference']);
    }

    public function testInitiatePayQrCodeOtherMerchant()
    {
        $helper = $this->getTransactionHelper();

        $helper->withSchemaValidated();

        $upi = [
            'mcc'       => '1010',
            'ref_url'   => 'https::example.com',
            'ref_id'    => 'XrefId'
        ];

        $coproto = $helper->initiatePay([
            'mode'  => 'qr_code',
            'upi'   => $upi,
            'payee' => [
                'id' => null,
                'username' => 'some',
                'handle'   => 'mybank',
                'type'     => 'vpa',
                'beneficiary_name' => 'benef_name',
            ]
        ]);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertSame(Mode::QR_CODE, $transaction->getMode());
        $this->assertSame($upi['mcc'], $transaction->upi->getMcc());
        $this->assertSame($upi['ref_url'], $transaction->upi->getRefUrl());
        $this->assertSame($upi['ref_id'], $transaction->upi->getRefId());

        $this->assertSame('SEND_MONEY', $coproto['request']['action']);

        $content = $coproto['request']['content'];

        $this->assertSame($upi['mcc'], $content['mcc']);
        $this->assertSame($upi['ref_url'], $content['refUrl']);
        $this->assertSame($upi['ref_id'], $content['transactionReference']);
    }

    public function testCollectAuthorizeCallbackForMerchant()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectIncomingTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT', [
            Fields::AMOUNT                  => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA               => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA               => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID          => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                 => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID     => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID    => $transaction->getCustomerId(),
            Fields::GATEWAY_TRANSACTION_ID  => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isCompleted());
        $this->assertArraySubset([
            Entity::STATUS          => Status::COMPLETED,
            Entity::INTERNAL_STATUS => Status::COMPLETED,
        ], $transaction->toArray());

        $this->assertArraySubset([
            UpiTransaction\Entity::GATEWAY_ERROR_CODE        => '00',
            UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION => 'Your transaction is approved',
            UpiTransaction\Entity::MCC                          => 2222,
            UpiTransaction\Entity::REF_URL                      => 'https::example.com',
        ], $transaction->upi->toArrayPublic());

        $this->assertSame('CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT', $transaction->upi->getGatewayData()['type']);
    }

    public function testTransactionMetricForPay()
    {
        $this->mockMetric();

        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $this->assertCountMetric(TransactionMetric::PSP_TRANSACTION_TOTAL, [
            TransactionMetric::DIMENSION_TYPE               => 'pay',
            TransactionMetric::DIMENSION_FLOW               => 'debit',
            TransactionMetric::DIMENSION_PREVIOUS_STATUS    =>  null
        ]);

        $this->mockSdkContentFunction(
            function(& $content)
            {
                $content['gatewayResponseCode']     = 'ZM';
                $content['gatewayResponseMessage']  = 'Wrong MPIN';
            });

        $content = $this->handleSdkRequest($coproto);

        $helper->authorizeTransaction($coproto['callback'], $content);

        $this->assertCountMetric(TransactionMetric::PSP_TRANSACTION_TOTAL, [
            TransactionMetric::DIMENSION_TYPE       => 'pay',
            TransactionMetric::DIMENSION_FLOW       => 'debit',
            TransactionMetric::DIMENSION_STATUS     => 'failed',
            TransactionMetric::DIMENSION_ERROR_CODE => 'BAD_REQUEST_ERROR',
        ], 1);
    }
}
