<?php

namespace RZP\Tests\P2p\Service\UpiAxis\Transaction;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Tests\P2p\Service\Base\Traits;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;

class TransactionEventTest extends TestCase
{
    use TestsWebhookEvents;
    use Traits\EventsTrait;
    use Traits\TransactionTrait;

    public function testPayCompleted()
    {
        $this->expectWebhookEvent(
            'customer.transaction.completed',
            function(array $event)
            {
                $this->assertArraySubset([
                    'type'                  => 'pay',
                    'flow'                  => 'debit',
                    'status'                => 'completed',
                    'is_pending_collect'    => false,
                ], $event['payload']);

                $this->assertNotNull($event['payload']['upi']['ref_id']);
                $this->assertNotNull($event['payload']['upi']['rrn']);
                $this->assertNotNull($event['payload']['upi']['network_transaction_id']);
            }
        );

        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePay();

        $content = $this->handleSdkRequest($coproto);

        $helper->authorizeTransaction($coproto['callback'], $content);
    }

    public function testPayToBankAccountCompleted()
    {
        $this->expectWebhookEvent(
            'customer.transaction.completed',
            function(array $event)
            {
                $this->assertArraySubset([
                    'type'                  => 'pay',
                    'flow'                  => 'debit',
                    'status'                => 'completed',
                    'is_pending_collect'    => false,
                ], $event['payload']);

                $bankAccount = $this->fixtures->bankAccount(self::DEVICE_2);
                $this->assertArraySubset([
                    'entity'                =>  'bank_account',
                    'id'                    =>  $bankAccount->getPublicId(),
                    'ifsc'                  =>  $bankAccount->getIfsc(),
                    'masked_account_number' =>  $bankAccount->getMaskedAccountNumber(),
                    'address'               =>  $bankAccount->getMaskedAccountNumber().'@'.$bankAccount->getIfsc().'.ifsc.npci',
                ], $event['payload']['payee']);

                $vpa = $this->fixtures->vpa(self::DEVICE_1);
                $this->assertArraySubset([
                    'entity'                =>  'vpa',
                    'handle'                =>  $vpa->getHandle(),
                    'id'                    =>  $vpa->getPublicId(),
                    'address'               =>  $vpa->getAddress(),
                    'username'              =>  $vpa->getUsername(),
                ], $event['payload']['payer']);

                $this->assertNotNull($event['payload']['upi']['ref_id']);
                $this->assertNotNull($event['payload']['upi']['rrn']);
                $this->assertNotNull($event['payload']['upi']['network_transaction_id']);
            }
        );

        $helper = $this->getTransactionHelper();

        $coproto = $helper->initiatePayToBankAccount();

        $content = $this->handleSdkRequest($coproto);

        $helper->authorizeTransaction($coproto['callback'], $content);
    }

    public function testPendingCollectWebhook()
    {
        $this->expectWebhookEvent(
            'customer.transaction.created',
            function(array $event)
            {
                $this->assertArraySubset([
                    'customer_id'           => 'cust_ArzpLocalCust1',
                    'type'                  => 'collect',
                    'flow'                  => 'debit',
                    'status'                => 'requested',
                    'is_pending_collect'    => true,
                ], $event['payload']);

                $this->assertArraySubset([
                    'mcc'                   => '2222',
                    'ref_url'               => 'https::example.com',
                ], $event['payload']['upi']);

                $this->assertNotNull($event['payload']['upi']['ref_id']);
                $this->assertNotNull($event['payload']['upi']['rrn']);
                $this->assertNotNull($event['payload']['upi']['network_transaction_id']);
                $this->assertArrayNotHasKey('bank_account', $event['payload']['payer']);
                $this->assertArrayNotHasKey('bank_account', $event['payload']['payee']);
                $this->assertSame('ALC01custVpa03@razoraxis', $event['payload']['payer']['address']);
                $this->assertSame('random@mypsp', $event['payload']['payee']['address']);
            }
        );

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
                'template'  => 'sms.p2p.collect_with_link',
                'sender'    => 'SENDER',
                'params'    => [
                    'app_name'          => 'APPLICATION NAME',
                    'payee_name'        => 'ALOCAL CUSTOMER',
                    'amount'            => 100,
                    'currency'          => 'INR',
                    'formatted_amount'  => '1.00',
                    'currency_label'    => 'Rs.',
                    'sms_signature'     => 'SMS SIGNATURE',
                    'app_collect_link'  => 'AppCollectLink',
                ],
            ], $input);
        });
    }

    public function testIncomingPay()
    {
        $this->expectWebhookEvent('customer.transaction.completed');

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
    }

    public function testIncomingPayFailed()
    {
        $this->expectWebhookEvent(
            'customer.transaction.failed',
            function(array $event)
            {
                $this->assertArraySubset([
                    'type'      => 'pay',
                    'flow'      => 'credit',
                    'status'    => 'failed'
                ], $event['payload']);

                $this->assertArraySubset([
                    'mcc'                   => '2222',
                    'ref_url'               => 'https::example.com',
                ], $event['payload']['upi']);

                $this->assertNotNull($event['payload']['upi']['ref_id']);
                $this->assertNotNull($event['payload']['upi']['rrn']);
                $this->assertNotNull($event['payload']['upi']['network_transaction_id']);
            }
        );

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
