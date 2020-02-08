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

class TransactionEventTest extends TestCase
{
    use Traits\EventsTrait;
    use Traits\TransactionTrait;

    public function testPayCompleted()
    {
        $this->setEventsForMerchant();

        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $content = $this->handleSdkRequest($coproto);

        $helper->authorizeTransaction($coproto['callback'], $content);

        $this->assertWebhookContent(function($content)
        {
            $this->assertSame('customer.transaction.completed', $content['event']);

            $this->assertArraySubset([
                'type'                  => 'pay',
                'flow'                  => 'debit',
                'status'                => 'completed',
                'is_pending_collect'    => false,
            ], $content['payload']);

            $this->assertNotNull($content['payload']['upi']['ref_id']);
            $this->assertNotNull($content['payload']['upi']['rrn']);
            $this->assertNotNull($content['payload']['upi']['network_transaction_id']);

        }, function($headers)
        {
            $this->assertNotNull($headers['X-Razorpay-Signature'][0]);
            $this->assertSame('www.example.com', $headers['Host'][0]);
        });

    }

    public function testPendingCollectWebhook()
    {
        $this->setEventsForMerchant();

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

        $this->mockRaven();

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $this->assertWebhookContent(function($content)
        {
            $this->assertSame('customer.transaction.created', $content['event']);

            $this->assertArraySubset([
                'customer_id'           => 'cust_ArzpLocalCust1',
                'type'                  => 'collect',
                'flow'                  => 'debit',
                'status'                => 'requested',
                'is_pending_collect'    => true,
            ], $content['payload']);

            $this->assertNotNull($content['payload']['upi']['ref_id']);
            $this->assertNotNull($content['payload']['upi']['rrn']);
            $this->assertNotNull($content['payload']['upi']['network_transaction_id']);
            $this->assertArrayNotHasKey('bank_account', $content['payload']['payer']);
            $this->assertArrayNotHasKey('bank_account', $content['payload']['payee']);
            $this->assertSame('ALC01custVpa03@razoraxis', $content['payload']['payer']['address']);
            $this->assertSame('random@mypsp', $content['payload']['payee']['address']);

        }, function($headers)
        {
            $this->assertNotNull($headers['X-Razorpay-Signature'][0]);
            $this->assertSame('www.example.com', $headers['Host'][0]);
        });
    }

    public function testPendingCollectNotification()
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

        $this->mockRaven();

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $this->assertRavenRequest(function($input)
        {
            $this->assertArraySubset([
                'receiver' => '+919988771111',
                'source'    => 'api.test.p2p',
                'template'  => 'sms.p2p.collect',
                'sender'    => 'RZRPAY',
                'params'    => [
                    'app_name'          => 'Razorpay Mobile Application',
                    'payee_name'        => 'ALOCAL CUSTOMER',
                    'amount'            => 100,
                    'currency'          => 'INR',
                    'formatted_amount'  => '1.00',
                    'currency_label'    => 'Rs.'
                ],
            ], $input);
        });
    }

    public function testIncomingPay()
    {
        $this->setEventsForMerchant();

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

        $this->assertWebhookContent(function($content)
        {
            $this->assertSame('customer.transaction.completed', $content['event']);
        });
    }

    public function testIncomingPayFailed()
    {
        $this->setEventsForMerchant();

        $helper = $this->getTransactionHelper();

        $gatewayTransactionId = str_random(35);
        $this->mockSdk()->setCallback('CUSTOMER_CREDITED_VIA_PAY', [
            Fields::AMOUNT                      => '1.00',
            Fields::PAYER_VPA                   => 'random@mypsp',
            Fields::PAYEE_VPA                   => $this->fixtures->vpa->getAddress(),
            Fields::UPI_REQUEST_ID              => 'RZP' . str_random(32),
            Fields::REMARKS                     => 'SomeTransaction',
            Fields::GATEWAY_TRANSACTION_ID      => $gatewayTransactionId,
            Fields::GATEWAY_RESPONSE_CODE       => 'U66',
            Fields::GATEWAY_RESPONSE_MESSAGE    => 'FAILURE',
            Fields::MERCHANT_CUSTOMER_ID        => $this->fixtures->deviceToken(self::DEVICE_1)
                                                    ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID]
        ]);

        $request = $this->mockSdk()->callback();
        $response = $helper->callback($this->gateway, $request);
        $this->assertTrue($response['success']);

        $this->assertWebhookContent(function($content)
        {
            $this->assertSame('customer.transaction.failed', $content['event']);

            $this->assertArraySubset([
                'type'      => 'pay',
                'flow'      => 'credit',
                'status'    => 'failed'
            ], $content['payload']);

            $this->assertNotNull($content['payload']['upi']['ref_id']);
            $this->assertNotNull($content['payload']['upi']['rrn']);
            $this->assertNotNull($content['payload']['upi']['network_transaction_id']);

        }, function($headers)
        {
            $this->assertNotNull($headers['X-Razorpay-Signature'][0]);
            $this->assertSame('www.example.com', $headers['Host'][0]);
        });
    }

    public function testEducationSms()
    {
        $this->mockReminder();

        $helper = $this->getTransactionHelper();

        $request = $helper->initiatePay();

        $content = $this->handleSdkRequest($request);

        $helper->withSchemaValidated();

        $helper->authorizeTransaction($request['callback'], $content);

        $transaction = $this->fixtures->getDbLastTransaction();

        $this->assertMockReminder(function ($request, $merchantId) use ($transaction) {
            $this->assertSame('p2p', $request['namespace']);
            $this->assertSame($transaction->getId(), $request['entity_id']);
            $this->assertSame($transaction->getEntityName(), $request['entity_type']);
        });
    }
}
