<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;


use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Gateway\Upi\Base\Secure;
use RZP\Models\Merchant\Account;
use RZP\Exception\RuntimeException;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiMindgatePaymentServiceTest extends UpiPaymentServiceTest
{
    use ReconTrait;
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/MindgateGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'upi_mindgate';
    }

    public function testPaymentSuccessWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_mindgate')->getAsyncCallbackContent($upi, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => $upi['npci_reference_id'],
            Entity::VPA => $upi['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE => Flow::COLLECT,
            UpiEntity::ACTION => 'authorize',
            UpiEntity::GATEWAY => $this->gateway,
            UpiEntity::STATUS_CODE => '00',
            UpiEntity::MERCHANT_REFERENCE => $payment['id']
        ], $upi);
    }

    public function testPaymentFailureWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->payment['vpa'] = 'failed@hdfcbank';

        $this->doAjaxPayment('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_mindgate')
            ->getAsyncCallbackContent($upi, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::FAILED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway,
            Entity::REFERENCE16 => null,
            Entity::CPS_ROUTE => 0,
            Entity::ERROR_CODE => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE => Flow::COLLECT,
            UpiEntity::ACTION => 'authorize',
            UpiEntity::GATEWAY => $this->gateway,
            UpiEntity::STATUS_CODE => 'ZA',
            UpiEntity::MERCHANT_REFERENCE => $payment['id']
        ], $upi);
    }

    public function testUpsPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_mindgate')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => '910000123456',
            Entity::VPA => $payment['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);
    }

    public function testUpsIntentPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_signed_intent_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $this->assertEquals('intent', $response['type']);
        $this->assertArrayHasKey('intent_url', $response['data']);
        $this->assertArrayHasKey('qr_code_url', $response['data']);

        $secure = new Secure([
            Secure::PUBLIC_KEY => $this->terminal['gateway_access_code'],
        ]);

        $this->assertTrue($secure->verifyIntent($response['data']['intent_url']));
        $this->assertTrue($secure->verifyIntent($response['data']['qr_code_url']));
    }

    public function testUpsPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->payment['vpa'] = 'failed@hdfcbank';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_mindgate')
            ->getAsyncCallbackContent($upiEntity, $payment);

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi');

        $this->assertNull($upi);

        $this->assertArraySubset([
            Entity::STATUS => Status::FAILED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway,
            Entity::REFERENCE16 => null,
            Entity::CPS_ROUTE => 4,
            Entity::ERROR_CODE => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER',
        ], $payment);
    }

    public function testUpsTpvPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['enable_addtl_info_upi']);

        $this->payment['notes']['Application Id'] = 'alphaNum123';

        $requestAsserted = false;

        $this->mockServerRequestFunction(function (&$content, $action = null) use (&$requestAsserted)
        {
            if ($action === 'authorize')
            {
                $requestAsserted = true;

                $this->assertEquals($this->payment['notes']['Application Id'], $content['metadata']['application_id']);
            }
        });

        $order = $this->createTpvOrder();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_tpv_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $this->assertTrue($requestAsserted);

        $content = $this->mockServer('upi_mindgate')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => '910000123456',
            Entity::VPA => $payment['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);
    }

    public function testUpsTpvPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->payment['vpa'] = 'failed@hdfcbank';
        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_tpv_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $upiEntity['gateway_payment_id'] = '12234';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_mindgate')
            ->getAsyncCallbackContent($upiEntity, $payment);

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi');

        $this->assertNull($upi);

        $this->assertArraySubset([
            Entity::STATUS => Status::FAILED,
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway,
            Entity::REFERENCE16 => null,
            Entity::CPS_ROUTE => 4,
            Entity::ERROR_CODE => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER',
        ], $payment);
    }

    public function testPartialRefund()
    {
        $payment = $this->testUpsPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        // Attempt a partial refund
        $this->refundPayment($payment['id'], 10000);

        $payment = $this->getDbLastPayment();

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals(10000, $payment->getAmountRefunded());

        // Mindgate refunds are processed via scrooge, so is_scrooge will be true
        $this->assertEquals($refund['is_scrooge'], true);

        $this->assertNotNull($refund['acquirer_data']['rrn']);

        // Attempt a partial refund
        $this->refundPayment($payment->getPublicId(), 10000);

        $payment = $payment->reload();

        $this->assertEquals(20000, $payment->getAmountRefunded());
    }

    public function testFullRefund()
    {
        $payment = $this->testUpsPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        // Attempt a partial refund
        $this->refundPayment($payment['id'], 50000);

        $payment = $this->getDbLastPayment();

        $refund = $this->getLastEntity('refund', true);

        // Mindgate refunds are processed via scrooge, so is_scrooge will be true
        $this->assertEquals($refund['is_scrooge'], true);

        $this->assertNotNull($refund['acquirer_data']['rrn']);

        $this->assertEquals(50000, $payment->getAmountRefunded());
    }

    public function testRefundFailure()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $payment = $this->testUpsPaymentSuccess();

        $this->mockServerGatewayContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        $refund = $this->refundPayment($payment['id'], 10000);

        $entity = $this->getEntityById('refund', $refund['id'], 'admin');

        //
        // For scrooge refunds, status will always be created.
        //
        $this->assertEquals('created', $entity['status']);

        $this->assertEquals(false, $entity['gateway_refunded']);

        $upi = $this->getDbLastEntity('upi');

        $this->assertEquals('BT', $upi['status_code']);
    }

    public function testRetryRefund()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $payment = $this->testUpsPaymentSuccess();

        $this->mockServerGatewayContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        $refund = $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        $this->mockServerGatewayContentFunction(function (&$content, $action = null) use($refund)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $refundId = substr($refund['id'], 5);

                $content[4] = 'SUCCESS';

                $this->assertEquals($refundId . 1, $content[1]);
            }
        });

        $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($refund['status'], 'processed');
    }

    public function testRetryRefundWithBankAccount()
    {
        $this->payment['vpa'] = 'failedrefund@hdfcbank';

        $payment = $this->testUpsPaymentSuccess();

        $this->mockServerGatewayContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        $refund = $this->refundPayment($payment['id'], 10000);

        $refund = $this->getLastEntity('refund', true);

        $this->mockServerGatewayContentFunction(function (&$content, $action = null) use($refund)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $refundId = substr($refund['id'], 5);

                $content[4] = 'SUCCESS';

                $this->assertEquals($refundId . 1, $content[1]);
            }
        });

        $bankAccountData =
            [
                'bank_account' => [
                    'ifsc_code'         => '12345678911',
                    'account_number'    => '123456789',
                    'beneficiary_name'  => 'test'
                ]
            ];

        $this->retryFailedRefund($refund['id'], $refund['payment_id'], $bankAccountData);

        $refund = $this->getLastEntity('refund', true);

        // Assert for fta created for given refund
        $fta = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($fta['source'], $refund['id']);

        $this->assertEquals('Test Merchant Refund ' . substr($payment['id'], 4), $fta['narration']);

        // Refund will be in created state
        $this->assertEquals($refund['status'], 'created');
    }

    protected function getVasCallbackContent()
    {
        $vasSecret = 'b2950f37dd1df3926749b0e1c50f6063';

        $terminalId = '100UPIMindgate';

        $this->fixtures->terminal->edit($terminalId, [
            'gateway_secure_secret' => $vasSecret,
        ]);

        $terminal = $this->getDbEntityById('terminal', $terminalId);

        $callbackMeta = [
            'key'           => hex2bin($vasSecret),
            'merchant_id'   => $terminal['gateway_merchant_id'],
        ];

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $payment = $this->getDbLastpayment()->toArray();

        if ($payment['cps_route'] === 4)
        {
            $upi['gateway_payment_id'] = '12234';
            $upi['payment_id'] = $payment['id'];
        }
        else
        {
            $upi = $this->getDBLastEntity('upi')->toArray();
        }

        return $this->mockServer('upi_mindgate')->getAsyncCallbackContent($upi, $payment, $callbackMeta);
    }

    public function testPaymentDecryptionFailedSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->gateway = 'upi_mindgate';

        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $content = $this->getVasCallbackContent();

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $upi = $this->getDBLastEntity('upi')->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => $upi['npci_reference_id'],
            Entity::VPA => $upi['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE => Flow::COLLECT,
            UpiEntity::ACTION => 'authorize',
            UpiEntity::GATEWAY => $this->gateway,
            UpiEntity::STATUS_CODE => '00',
            UpiEntity::MERCHANT_REFERENCE => $payment['id']
        ], $upi);
    }

    public function testUpsPaymentDecryptionFailedSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $content = $this->getVasCallbackContent();

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 4,
        ], $payment);

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS => Status::AUTHORIZED,
            Entity::REFERENCE16 => '910000123456',
            Entity::VPA => $payment['vpa'],
            Entity::TERMINAL_ID => $this->terminal->getId(),
            Entity::GATEWAY => $this->gateway
        ], $payment);
    }

    public function testPaymentDecryptionFailedWithNoTerminalFound()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->payment['vpa'] = 'noTerminal@hdfcbank';

        $this->doAjaxPayment('terminal:shared_upi_mindgate_terminal', 'upi_mindgate');

        $this->setRazorxMock(function ($mid, $feature, $mode) {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $payment = $this->getDbLastpayment()->toArray();

        $this->assertArraySubset([
            Entity::CPS_ROUTE => 0,
        ], $payment);

        $upi = $this->getDBLastEntity('upi')->toArray();

        $content = $this->mockServer('upi_mindgate')
            ->getAsyncCallbackContent($upi, $payment);

        $this->makeRequestAndCatchException(
            function () use ($content)
            {
                $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');
            },
            RuntimeException::class,
            'No terminal found');
    }

    public function testUnexpectedPaymentWithV2PreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_mindgate';

        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_mindgate_pre_process_v1', 'upi_mindgate');
        });

        $content = $this->unexpectedPaymentContent();

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_mindgate');

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $this->assertEquals(
            [
                'success' => true
            ], $response
        );

        $this->assertNotEquals($authorizeUpiEntity['merchant_reference'], $paymentEntity['id']);

        $assertEqualsMap = [
            'authorized'                              => $paymentEntity['status'],
            'authorize'                               => $authorizeUpiEntity['action'],
            'pay'                                     => $authorizeUpiEntity['type'],
            $paymentEntity['id']                      => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']           => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id']    => $paymentEntity['id'],
            $paymentTransactionEntity['type']         => 'payment',
            $paymentTransactionEntity['amount']       => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                     => $paymentEntity['merchant_id'],
            $authorizeUpiEntity['gateway']            => $paymentEntity['gateway'],
            $authorizeUpiEntity['amount']             => $paymentEntity['amount'],
            $paymentEntity['amount']                  => 227924,
            $authorizeUpiEntity['merchant_reference'] => 'paysucc123'
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    protected function unexpectedPaymentContent()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $data = $this->testData['testUnexpectedPaymentSuccess'];

        $data['meRes'] = $this->mockServer()->encrypt($data['meRes']);

        return $data;
    }

    public function testPaymentRecon()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }

        $upiEntity1 = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $this->shouldCreateTerminal = false;

        $upiEntity2 = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $entries[] = $this->overrideUpiHdfcPayment($upiEntity1);

        $row = $this->overrideUpiHdfcPayment($upiEntity2);

        // Change the settlement date format for this row, to test
        // that this format is being parsed correctly without error.
        $row['Settlement Date'] = '08/19/2018';

        $entries[] = $row;

        $file = $this->writeToExcelFile($entries, 'upiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNotNull($payment['reference16']);

            $this->assertEquals($entries[1]['Txn ref no. (RRN)'], $payment['reference16']);
        }

        $this->assertBatchStatus('processed');

        $updatedPayment1 = $this->getDbEntityById('payment', $upiEntity1['payment_id']);
        $updatedPayment2 = $this->getDbEntityById('payment', $upiEntity2['payment_id']);

        $transactionEntity1 = $this->getDbEntityById('transaction', $updatedPayment1['transaction_id']);
        $transactionEntity2 = $this->getDbEntityById('transaction', $updatedPayment2['transaction_id']);

        $this->assertNotNull($transactionEntity1['reconciled_at']);
        $this->assertNotNull($transactionEntity2['reconciled_at']);

        $this->assertNotNull($transactionEntity1['gateway_settled_at']);
        $this->assertNotNull($transactionEntity2['gateway_settled_at']);

        $upiEntity2 = $this->getDbLastEntityToArray('upi');
    }

    public function testUnexpectedPaymentSuccess()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $paymentEntity = $this->getDbLastPayment();

        $entries[] = $this->overrideUpiHdfcPayment([
            'payment_id'          => 'EHloDoL0yeRPV0123',
            'npci_reference_id'   => '1234567890'
        ]);

        $file = $this->writeToExcelFile($entries, 'UpiHdfc');

        $this->mockServerContentFunction(
            function (&$response)
            {
                $response = [];
            }
        );

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $unexpectedPayment = $this->getDbLastPayment();

        $this->assertNotEquals($unexpectedPayment['id'], $paymentEntity['id']);

        $this->assertNotNull($unexpectedPayment['reference16']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $unexpectedUpiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('EHloDoL0yeRPV0123', $unexpectedUpiEntity['merchant_reference']);

        $this->assertEquals('1234567890', $unexpectedUpiEntity['npci_reference_id']);

        $this->assertNotNull($unexpectedUpiEntity['reconciled_at']);

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($transaction['gateway_settled_at']);
    }

    public function testUnexpectedPaymentRrnFetch()
    {
        $this->payment = $this->getDefaultUpiPaymentArray();

        $upiEntity = $this->getNewUpiEntity('10000000000000', 'upi_mindgate');

        $payment = $this->getDbLastPayment();

        $paymentId  = $payment->getId();

        $this->mockServerContentFunction(
            function (&$response) use ($paymentId)
            {
                $response['entity']['payment_id'] = $paymentId;
            }
        );

        $entries[] = $this->overrideUpiHdfcPayment([
            'payment_id'          => 'EHloDoL0yeRPV0123',
            'npci_reference_id'   => '1234567890'
        ]);

        $file = $this->writeToExcelFile($entries, 'UpiHdfc');

        $uploadedFile = $this->createUploadedFile($file);

        $this->reconcile($uploadedFile, 'UpiHdfc');

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertEquals($payment->getTransactionId(), $transaction['id']);

        $this->assertNotNull($transaction['gateway_settled_at']);
    }

    protected function overrideUpiHdfcPayment(array $upiEntity)
    {
        $facade = $this->testData['upiHdfc'];

        $facade['Order ID'] = $upiEntity['payment_id'];

        $facade['Txn ref no. (RRN)'] = $upiEntity['npci_reference_id'];

        return $facade;
    }

    protected function getNewUpiEntity($merchantId, $gateway, $mockServer = null)
    {
        $this->testUpsPaymentSuccess();

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals(4, $payment['cps_route']);

        $this->gateway = 'upi_axis';

        $upiEntity['npci_reference_id'] = $payment['reference16'];

        $upiEntity['payment_id'] = $payment['id'];

        return $upiEntity;
    }

    protected function mockServerContentFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('content')->andReturnUsing($closure);
    }

    protected function mockServerRequestFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('request')->andReturnUsing($closure);
    }
}
