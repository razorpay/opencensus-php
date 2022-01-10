<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Status;
use RZP\Tests\P2p\Service\Base\Traits;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Transaction\UpiTransaction;

class TransactionFailureTest extends TestCase
{
    use Traits\TransactionTrait;

    public function testPayAuthorizeSdkUnauthorized()
    {
        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $this->mockSdk()->withError('UNAUTHORIZED');

        $content = $this->handleSdkRequest($coproto);

        $this->withFailureResponse($helper, function($error) {
            $this->assertArraySubset([
                                         'code'        => 'GATEWAY_ERROR',
                                         'description' => 'Token is invalid or expired'
                                     ], $error);
        }, 502);

        $helper->authorizeTransaction($coproto['callback'], $content);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isCreated());
    }

    public function testPayAuthorizeWrongMpin()
    {
        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $this->mockSdkContentFunction(
            function(&$content) {
                $content['gatewayResponseCode']    = 'ZM';
                $content['gatewayResponseMessage'] = 'Wrong MPIN';
            });

        $content = $this->handleSdkRequest($coproto);

        $response = $helper->authorizeTransaction($coproto['callback'], $content);

        $this->assertSame('failed', $response['status']);
        $this->assertSame('BAD_REQUEST_ERROR', $response['error_code']);
        $this->assertSame('ZM', $response['upi']['gateway_error_code']);
        $this->assertSame('Wrong MPIN', $response['upi']['gateway_error_description']);
    }

    public function testPayAuthorizePending()
    {
        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $this->mockSdkContentFunction(
            function(&$content) {
                $content['gatewayResponseCode']    = 'BT';
                $content['gatewayResponseMessage'] = 'Transaction pending';
            });

        $content = $this->handleSdkRequest($coproto);

        $response = $helper->authorizeTransaction($coproto['callback'], $content);

        $this->assertSame('pending', $response['status']);
        $this->assertSame('GATEWAY_ERROR', $response['error_code']);
        $this->assertSame('Transaction is in pending state', $response['error_description']);
        $this->assertSame('BT', $response['upi']['gateway_error_code']);
        $this->assertSame('Transaction pending', $response['upi']['gateway_error_description']);
    }

    public function testCollectExpired()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                   => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID           => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                  => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID      => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID     => $transaction->getCustomerId(),
            Fields::GATEWAY_RESPONSE_CODE    => 'U69',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Collect expired',
            Fields::GATEWAY_TRANSACTION_ID   => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isFailed());
        $this->assertArraySubset([
                                     Entity::STATUS          => Status::EXPIRED,
                                     Entity::INTERNAL_STATUS => Status::EXPIRED,
                                 ], $transaction->toArray());

        $this->assertArraySubset([
                                     UpiTransaction\Entity::GATEWAY_ERROR_CODE        => 'U69',
                                     UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION => 'Collect expired'
                                 ], $transaction->upi->toArrayPublic());
    }

    public function testCollectExpiredOnHardFailure()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                   => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID           => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                  => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID      => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID     => $transaction->getCustomerId(),
            Fields::GATEWAY_RESPONSE_CODE    => 'U69',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Collect expired',
            Fields::GATEWAY_TRANSACTION_ID   => $transaction->payer->getAddress(),
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);

        $this->assertTrue($response['success']);
        $this->assertEquals('Count of UPI should be exactly one', $response['expected_hard_failure'],
                            'Expected hard failure exception is not thrown');
    }

    public function testCollectOnHardFailureAfterExceedingLimit()
    {
        $helper = $this->getTransactionHelper();

        $expiry = (clone $this->testCurrentTime)->timezone('Asia/Kolkata')->addDay(1);

        $gatewayTransactionId = str_random(35);

        $this->mockSdk()->setCallback('COLLECT_REQUEST_RECEIVED', [
            Fields::AMOUNT                 => '5001.00', // Amount above 2000 for incoming collect
            Fields::PAYEE_VPA              => 'random@mypsp',
            Fields::PAYER_VPA              => $this->fixtures->vpa->getAddress(),
            Fields::UPI_REQUEST_ID         => 'RZP' . str_random(32),
            Fields::REMARKS                => 'SomeTransaction',
            Fields::GATEWAY_TRANSACTION_ID => $gatewayTransactionId,
            Fields::EXPIRY                 => $expiry->toIso8601String(),
            Fields::MERCHANT_CUSTOMER_ID   => $this->fixtures->deviceToken(self::DEVICE_1)
                                                             ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID]
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);
        $this->assertEquals('First transaction can not be more than 5000 rupees.', $response['expected_hard_failure'],
                            'Expected hard failure exception is not thrown');
    }

    public function testCollectRejected()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                   => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID           => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                  => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID      => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID     => $transaction->getCustomerId(),
            Fields::GATEWAY_RESPONSE_CODE    => 'ZA',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Collect rejected',
            Fields::GATEWAY_TRANSACTION_ID   => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isFailed());
        $this->assertArraySubset([
                                     Entity::STATUS => Status::REJECTED,
                                 ], $transaction->toArrayPublic());

        $this->assertArraySubset([
                                     UpiTransaction\Entity::GATEWAY_ERROR_CODE        => 'ZA',
                                     UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION => 'Collect rejected'
                                 ], $transaction->upi->toArrayPublic());
    }

    public function testCollectDeemed()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                   => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID           => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                  => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID      => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID     => $transaction->getCustomerId(),
            Fields::GATEWAY_RESPONSE_CODE    => 'BT',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Transaction pending',
            Fields::GATEWAY_TRANSACTION_ID   => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isProcessing());
        $this->assertArraySubset([
                                     Entity::STATUS => Status::PENDING,
                                 ], $transaction->toArrayPublic());

        $this->assertArraySubset([
                                     UpiTransaction\Entity::GATEWAY_ERROR_CODE        => 'BT',
                                     UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION => 'Transaction pending'
                                 ], $transaction->upi->toArrayPublic());
    }

    public function testPayDeemed()
    {
        $helper = $this->getTransactionHelper();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_PAY', [
            Fields::AMOUNT                   => '1.00',
            Fields::PAYER_VPA                => 'random@mypsp',
            Fields::PAYEE_VPA                => $this->fixtures->vpa->getAddress(),
            Fields::UPI_REQUEST_ID           => 'RZP' . str_random(32),
            Fields::REMARKS                  => 'SomeTransaction',
            Fields::GATEWAY_RESPONSE_CODE    => '01',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Transaction pending',
            Fields::MERCHANT_CUSTOMER_ID     => $this->fixtures->deviceToken(self::DEVICE_1)
                                                               ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID],
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->getDbLastTransaction();

        $this->assertArraySubset([
                                     Entity::CUSTOMER_ID       => $this->fixtures->device->getCustomerId(),
                                     Entity::STATUS            => Status::PENDING,
                                     Entity::INTERNAL_STATUS   => Status::PENDING,
                                     Entity::ERROR_CODE        => 'GATEWAY_ERROR',
                                     Entity::ERROR_DESCRIPTION => 'Transaction is in pending state',
                                     Entity::PAYEE_ID          => $this->fixtures->vpa->getId(),
                                     Entity::BANK_ACCOUNT_ID   => $this->fixtures->vpa->getBankAccountId(),
                                 ], $transaction->reload()->toArray());
    }

    public function testPayFailedAtBank()
    {
        $helper = $this->getTransactionHelper();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_PAY', [
            Fields::AMOUNT                   => '1.00',
            Fields::PAYER_VPA                => 'random@mypsp',
            Fields::PAYEE_VPA                => $this->fixtures->vpa->getAddress(),
            Fields::UPI_REQUEST_ID           => 'RZP' . str_random(32),
            Fields::REMARKS                  => 'SomeTransaction',
            Fields::GATEWAY_RESPONSE_CODE    => 'U16',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Transaction failed',
            Fields::MERCHANT_CUSTOMER_ID     => $this->fixtures->deviceToken(self::DEVICE_1)
                                                               ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID],
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->getDbLastTransaction();

        $this->assertArraySubset([
                                     Entity::CUSTOMER_ID     => $this->fixtures->device->getCustomerId(),
                                     Entity::STATUS          => Status::FAILED,
                                     Entity::INTERNAL_STATUS => Status::FAILED,
                                     Entity::PAYEE_ID        => $this->fixtures->vpa->getId(),
                                     Entity::BANK_ACCOUNT_ID => $this->fixtures->vpa->getBankAccountId(),
                                 ], $transaction->reload()->toArray());
    }

    public function testCollectAcceptedAmountMismatch()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $transaction->setAmount(900);

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                 => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA              => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA              => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID         => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID    => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID   => $transaction->getCustomerId(),
            Fields::GATEWAY_TRANSACTION_ID => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isFailed());
        $this->assertArraySubset([
                                     Entity::STATUS => Status::FAILED,
                                 ], $transaction->toArrayPublic());

        $this->assertArraySubset([
                                     UpiTransaction\Entity::GATEWAY_ERROR_CODE        => '00',
                                     UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION => 'Your transaction is approved'
                                 ], $transaction->upi->toArrayPublic());
    }

    public function testCollectIncomingExpired()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectIncomingTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_DEBITED_VIA_COLLECT', [
            Fields::AMOUNT                   => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                => $transaction->payee->getAddress(),
            Fields::GATEWAY_TRANSACTION_ID   => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                  => $transaction->getDescription(),
            Fields::MERCHANT_CUSTOMER_ID     => $transaction->getCustomerId(),
            Fields::GATEWAY_RESPONSE_CODE    => 'U69',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Collect expired',
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isFailed());
        $this->assertArraySubset([
                                     Entity::STATUS          => Status::EXPIRED,
                                     Entity::INTERNAL_STATUS => Status::EXPIRED,
                                 ], $transaction->toArray());

        $this->assertArraySubset([
                                     UpiTransaction\Entity::GATEWAY_ERROR_CODE        => 'U69',
                                     UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION => 'Collect expired'
                                 ], $transaction->upi->toArrayPublic());
    }

    public function testCollectIncomingRejected()
    {
        $helper = $this->getTransactionHelper();

        $transaction = $this->createCollectTransaction();

        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_COLLECT', [
            Fields::AMOUNT                   => $transaction->getRupeesAmount(),
            Fields::PAYER_VPA                => $transaction->payer->getAddress(),
            Fields::PAYEE_VPA                => $transaction->payee->getAddress(),
            Fields::UPI_REQUEST_ID           => $transaction->upi->getNetworkTransactionId(),
            Fields::REMARKS                  => $transaction->getDescription(),
            Fields::MERCHANT_REQUEST_ID      => $transaction->getId(),
            Fields::MERCHANT_CUSTOMER_ID     => $transaction->getCustomerId(),
            Fields::GATEWAY_RESPONSE_CODE    => 'ZA',
            Fields::GATEWAY_RESPONSE_MESSAGE => 'Collect rejected',
            Fields::GATEWAY_TRANSACTION_ID   => $transaction->upi->getNetworkTransactionId(),
        ]);

        $request  = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertTrue($transaction->isFailed());
        $this->assertArraySubset([
                                     Entity::STATUS => Status::REJECTED,
                                 ], $transaction->toArrayPublic());

        $this->assertArraySubset([
                                     UpiTransaction\Entity::GATEWAY_ERROR_CODE        => 'ZA',
                                     UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION => 'Collect rejected'
                                 ], $transaction->upi->toArrayPublic());
    }

    public function testInitiatePayToSameDevice()
    {
        $helper = $this->getTransactionHelper();

        $vpa = $this->fixtures->createVpa([]);

        $this->withFailureResponse($helper, function($error) {
            $this->assertArraySubset([
                                         'code'        => 'BAD_REQUEST_ERROR',
                                         'description' => 'Payer and Payee bank account should not be same'
                                     ], $error);
        });

        $helper->initiatePay([
                                 'payee' => [
                                     'id'   => $vpa->getPublicId(),
                                     'type' => 'vpa'
                                 ],
                             ]);
    }

    public function testInitiateCollectToSameDevice()
    {
        $helper = $this->getTransactionHelper();

        $vpa = $this->fixtures->createVpa([]);

        $this->withFailureResponse($helper, function($error) {
            $this->assertArraySubset([
                                         'code'        => 'BAD_REQUEST_ERROR',
                                         'description' => 'Payer and Payee bank account should not be same'
                                     ], $error);
        });

        $helper->initiateCollect([
                                     'payer' => [
                                         'id'   => $vpa->getPublicId(),
                                         'type' => 'vpa'
                                     ],
                                 ]);
    }

}
